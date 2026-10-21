<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\SupplierBatch;
use App\Models\SupplierReturn;
use App\Models\User;
use App\Services\Ai\MaterialDefectVisionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Seconde vie de la matière entre marque et fournisseur :
 * la marque renvoie (surplus, défaut, erreur), le fournisseur reprend et valorise,
 * TexTileCycle vérifie la preuve. Chaque étape notifie l'acteur suivant.
 */
class SupplierReturnService
{
    /** Écart toléré entre kg renvoyés et kg déclarés en seconde vie (pesée, humidité). */
    public const BALANCE_TOLERANCE = 0.05;

    public function __construct(
        private NotificationCenter $notifications,
        private MaterialDefectVisionService $vision,
    ) {}

    /** Kg encore renvoyables sur un lot : reçu − déjà renvoyé. */
    public function returnableKg(SupplierBatch $batch): float
    {
        if (! $batch->received_at) {
            return 0.0;
        }

        return max(0.0, round((float) $batch->quantity_kg - (float) SupplierReturn::where('supplier_batch_id', $batch->id)->sum('quantity_kg'), 2));
    }

    public function declare(Brand $brand, SupplierBatch $batch, array $data, ?UploadedFile $photo, User $by): SupplierReturn
    {
        abort_unless($batch->supplier?->brand_id === $brand->id, 403);

        $return = DB::transaction(function () use ($brand, $batch, $data, $photo, $by) {
            // Verrou sur le lot : deux déclarations simultanées ne dépassent pas la quantité reçue.
            $batch = SupplierBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $available = $this->returnableKg($batch);
            if (! $batch->received_at) {
                throw ValidationException::withMessages(['quantity_kg' => 'Seul un lot réceptionné peut être renvoyé.']);
            }
            if ((float) $data['quantity_kg'] > $available) {
                throw ValidationException::withMessages(['quantity_kg' => 'Au maximum '.number_format($available, 2, ',', ' ').' kg peuvent encore être renvoyés sur ce lot.']);
            }

            return SupplierReturn::create([
                'reference' => 'RTV-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'brand_id' => $brand->id,
                'supplier_id' => $batch->supplier_id,
                'supplier_batch_id' => $batch->id,
                'purchase_order_id' => $batch->purchase_order_id,
                'material_id' => $batch->material_id,
                'requested_by' => $by->id,
                'quantity_kg' => round((float) $data['quantity_kg'], 2),
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'photo_path' => $photo?->store('supplier-returns', 'local'),
                'status' => 'requested',
            ]);
        });

        if ($return->photo_path) {
            $return->update(['ai_analysis' => $this->vision->analyse(Storage::disk('local')->path($return->photo_path), $return->reason)]);
        }

        $this->notifications->send($return->supplier->user_id, 'supplier_return_requested', 'Retour de matière de '.$brand->name,
            $return->reference.' : '.$this->kg($return->quantity_kg).' de '.($return->material?->name ?? 'matière').' ('.mb_strtolower($return->reasonLabel()).'). Choisissez sa seconde vie.',
            route('supplier.returns.show', $return));

        return $return;
    }

    public function accept(SupplierReturn $return, array $data): SupplierReturn
    {
        $this->guard($return, ['requested'], 'Ce retour a déjà été pris en charge.');
        $return->update([
            'status' => 'accepted', 'accepted_at' => now(),
            'destination' => $data['destination'], 'pickup_at' => $data['pickup_at'] ?? null, 'supplier_note' => $data['supplier_note'] ?? null,
        ]);
        $this->notifyBrand($return, 'supplier_return_accepted', 'Reprise acceptée par '.$return->supplier->name,
            $return->reference.' : '.mb_strtolower($return->destinationLabel()).($return->pickup_at ? ', enlèvement prévu le '.$return->pickup_at->format('d/m/Y') : '').'. Renvoyez la matière.');

        return $return;
    }

    public function ship(SupplierReturn $return): SupplierReturn
    {
        $this->guard($return, ['accepted'], 'Le fournisseur doit d’abord accepter la reprise.');
        $return->update(['status' => 'in_transit', 'shipped_at' => now()]);
        $this->notifications->send($return->supplier->user_id, 'supplier_return_shipped', 'Matière renvoyée par '.$return->brand->name,
            $return->reference.' : '.$this->kg($return->quantity_kg).' en route. Confirmez la réception à l’arrivée.', route('supplier.returns.show', $return));

        return $return;
    }

    public function receive(SupplierReturn $return): SupplierReturn
    {
        $this->guard($return, ['in_transit'], 'La marque n’a pas encore renvoyé la matière.');
        $return->update(['status' => 'received', 'received_at' => now()]);
        $this->notifyBrand($return, 'supplier_return_received', 'Matière reçue par '.$return->supplier->name, $return->reference.' : réception confirmée.');

        return $return;
    }

    /** Le fournisseur déclare ce que la matière est devenue, preuve à l'appui. */
    public function complete(SupplierReturn $return, array $data, UploadedFile $proof): SupplierReturn
    {
        $this->guard($return, ['received', 'correction'], 'La matière doit être reçue avant de déclarer sa seconde vie.');
        $declared = (float) $data['resold_kg'] + (float) $data['recycled_kg'] + (float) $data['upcycled_kg'] + (float) $data['waste_kg'];
        $expected = (float) $return->quantity_kg;
        if (abs($declared - $expected) > $expected * self::BALANCE_TOLERANCE) {
            throw ValidationException::withMessages(['resold_kg' => 'Le total déclaré ('.$this->kg($declared).') doit correspondre aux '.$this->kg($expected).' reçus (± '.(int) (self::BALANCE_TOLERANCE * 100).' %).']);
        }

        $return->update([
            'status' => 'completed', 'completed_at' => now(),
            'resold_kg' => $data['resold_kg'], 'recycled_kg' => $data['recycled_kg'], 'upcycled_kg' => $data['upcycled_kg'], 'waste_kg' => $data['waste_kg'],
            'supplier_note' => $data['supplier_note'] ?? $return->supplier_note,
            'proof_path' => $proof->store('supplier-returns/proofs', 'local'),
        ]);
        $this->notifications->toAdmins('supplier_return_completed', 'Seconde vie à vérifier',
            $return->supplier->name.' déclare '.$this->kg($return->savedKg()).' valorisés sur '.$this->kg($expected).' ('.$return->reference.').', route('admin.supplier-returns.show', $return));
        $this->notifyBrand($return, 'supplier_return_completed', 'Seconde vie déclarée', $return->reference.' : '.$this->kg($return->savedKg()).' valorisés. Vérification TexTileCycle en cours.');

        return $return;
    }

    public function verify(SupplierReturn $return, User $admin, bool $approve, ?string $note): SupplierReturn
    {
        $this->guard($return, ['completed'], 'Rien à vérifier sur ce retour pour le moment.');
        if (! $approve && blank($note)) {
            throw ValidationException::withMessages(['admin_note' => 'Expliquez au fournisseur ce qu’il doit corriger.']);
        }
        $return->update([
            'status' => $approve ? 'verified' : 'correction', 'admin_note' => $note,
            'verified_by' => $approve ? $admin->id : null, 'verified_at' => $approve ? now() : null,
        ]);

        $title = $approve ? 'Seconde vie vérifiée' : 'Correction demandée';
        $message = $return->reference.($approve ? ' : '.$this->kg($return->savedKg()).' de matière sauvés du déchet.' : ' : « '.$note.' »');
        $this->notifications->send($return->supplier->user_id, 'supplier_return_'.($approve ? 'verified' : 'correction'), $title, $message, route('supplier.returns.show', $return));
        $this->notifyBrand($return, 'supplier_return_'.($approve ? 'verified' : 'correction'), $title, $message);

        return $return;
    }

    private function guard(SupplierReturn $return, array $statuses, string $message): void
    {
        if (! in_array($return->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function notifyBrand(SupplierReturn $return, string $type, string $title, string $message): void
    {
        $this->notifications->send($return->brand?->user_id, $type, $title, $message, route('brand.supplier-returns.show', $return));
    }

    private function kg(float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', ' ').' kg';
    }
}
