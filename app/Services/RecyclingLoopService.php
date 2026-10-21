<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Material;
use App\Models\Product;
use App\Models\RecyclingLoop;
use App\Models\RecyclingLoopItem;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\Ai\MaterialDefectVisionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Boucle de recyclage marque ↔ fournisseur. Chaque étape vérifie l'état attendu,
 * enregistre la date et prévient l'acteur suivant.
 */
class RecyclingLoopService
{
    /** Le fournisseur pèse ce qu'il reçoit : écart toléré avec le poids annoncé par la marque. */
    public const WEIGHT_TOLERANCE = 0.10;

    public function __construct(
        private NotificationCenter $notifications,
        private MaterialDefectVisionService $vision,
        private RecyclingEstimator $estimator,
        private SupplierTraceabilityService $traceability,
    ) {}

    /**
     * @param  array<int, array{kind: string, label: string, product_id?: ?int, material_id?: ?int, pieces?: ?int, quantity_kg: float, issue?: ?string}>  $items
     * @param  array<int, UploadedFile|null>  $photos  indexées comme $items
     */
    public function request(Brand $brand, Supplier $supplier, array $items, array $photos, array $data, User $by): RecyclingLoop
    {
        abort_unless($supplier->brand_id === $brand->id, 403);
        if (! $supplier->user_id) {
            throw ValidationException::withMessages(['supplier_id' => 'Ce fournisseur n’a pas encore activé son espace : il ne peut pas recevoir la demande.']);
        }

        $loop = DB::transaction(function () use ($brand, $supplier, $items, $data, $by) {
            $loop = RecyclingLoop::create([
                'reference' => 'RCY-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
                'brand_id' => $brand->id,
                'supplier_id' => $supplier->id,
                'requested_by' => $by->id,
                'status' => 'requested',
                'brand_message' => $data['brand_message'] ?? null,
                'wished_pickup_at' => $data['wished_pickup_at'] ?? null,
            ]);
            foreach ($items as $item) {
                $loop->items()->create([
                    'kind' => $item['kind'],
                    'label' => filled($item['label'] ?? null) ? $item['label'] : $this->autoLabel($item),
                    'product_id' => $item['product_id'] ?? null,
                    'material_id' => $item['material_id'] ?? null,
                    'pieces' => $item['pieces'] ?? null,
                    'quantity_kg' => round((float) $item['quantity_kg'], 2),
                    'issue' => $item['issue'] ?? null,
                ]);
            }

            return $loop;
        });

        // Photos : stockées puis analysées par l'IA (défauts visibles).
        foreach ($loop->items()->orderBy('id')->get()->values() as $index => $item) {
            $photo = $photos[$index] ?? null;
            if ($photo instanceof UploadedFile) {
                $path = $photo->store('recycling-loops', 'local');
                $item->update([
                    'photo_path' => $path,
                    'ai_analysis' => $this->vision->analyse(Storage::disk('local')->path($path), $item->kind === 'defective' ? 'defect' : 'surplus'),
                ]);
            }
        }

        $loop->load('items');
        $this->notifySupplier($loop, 'recycling_requested', $brand->name.' veut vous confier des articles à recycler',
            $this->kg($loop->totalKg()).' ('.$loop->items->count().' lot(s) : '.$loop->items->pluck('label')->join(', ').'). Faites votre proposition.');

        return $loop;
    }

    public function propose(RecyclingLoop $loop, array $data): RecyclingLoop
    {
        $this->guard($loop, ['requested'], 'Une proposition a déjà été faite.');
        $loop->update([
            'status' => 'proposed', 'proposed_at' => now(),
            'output_material_id' => $data['output_material_id'], 'expected_output_kg' => $data['expected_output_kg'],
            'mode' => $data['mode'], 'unit_price' => $data['unit_price'] ?? 0, 'ready_by' => $data['ready_by'] ?? null,
            'supplier_message' => $data['supplier_message'] ?? null,
        ]);
        $loop->load('outputMaterial');
        $this->notifyBrand($loop, 'recycling_proposed', $loop->supplier->name.' vous fait une proposition',
            $loop->reference.' : '.$this->kg($loop->expected_output_kg).' de '.$loop->outputMaterial?->name.' · '.mb_strtolower($loop->modeLabel())
            .($loop->amount() ? ' · '.number_format($loop->amount(), 2, ',', ' ').' TND' : ' · sans frais').'.');

        return $loop;
    }

    public function decline(RecyclingLoop $loop, string $reason, string $by): RecyclingLoop
    {
        $this->guard($loop, $by === 'supplier' ? ['requested'] : ['proposed'], 'Cette boucle ne peut plus être refusée à cette étape.');
        $loop->update(['status' => 'declined', 'declined_at' => now(), 'decline_reason' => $reason]);
        if ($by === 'supplier') {
            $this->notifyBrand($loop, 'recycling_declined', $loop->supplier->name.' ne peut pas reprendre ces articles', $loop->reference.' : « '.$reason.' »');
        } else {
            $this->notifySupplier($loop, 'recycling_declined', $loop->brand->name.' a refusé votre proposition', $loop->reference.' : « '.$reason.' »');
        }

        return $loop;
    }

    public function accept(RecyclingLoop $loop): RecyclingLoop
    {
        $this->guard($loop, ['proposed'], 'Aucune proposition en attente.');
        $loop->update(['status' => 'accepted', 'accepted_at' => now()]);
        $this->notifySupplier($loop, 'recycling_accepted', 'Proposition acceptée par '.$loop->brand->name, $loop->reference.' : la marque prépare l’envoi des articles.');

        return $loop;
    }

    public function ship(RecyclingLoop $loop): RecyclingLoop
    {
        $this->guard($loop, ['accepted'], 'La proposition doit d’abord être acceptée.');
        $loop->update(['status' => 'in_transit', 'shipped_at' => now()]);
        $loop->load('items');
        $this->notifySupplier($loop, 'recycling_shipped', 'Articles en route de '.$loop->brand->name, $loop->reference.' : '.$this->kg($loop->totalKg()).' expédiés. Pesez-les à l’arrivée.');

        return $loop;
    }

    public function receive(RecyclingLoop $loop, float $receivedKg): RecyclingLoop
    {
        $this->guard($loop, ['in_transit'], 'Les articles ne sont pas encore en route.');
        $loop->load('items');
        $announced = $loop->totalKg();
        if (abs($receivedKg - $announced) > $announced * self::WEIGHT_TOLERANCE) {
            throw ValidationException::withMessages(['received_kg' => 'Le poids pesé s’écarte de plus de '.(int) (self::WEIGHT_TOLERANCE * 100).' % des '.$this->kg($announced).' annoncés : vérifiez la pesée.']);
        }
        $loop->update(['status' => 'received', 'received_at' => now(), 'received_kg' => round($receivedKg, 2)]);
        $this->notifyBrand($loop, 'recycling_received', 'Articles reçus par '.$loop->supplier->name, $loop->reference.' : '.$this->kg($receivedKg).' pesés à l’arrivée. Le recyclage commence.');

        return $loop;
    }

    public function transform(RecyclingLoop $loop, float $outputKg, UploadedFile $proof): RecyclingLoop
    {
        $this->guard($loop, ['received'], 'Les articles doivent être reçus avant d’être recyclés.');
        if ($outputKg > (float) $loop->received_kg) {
            throw ValidationException::withMessages(['output_kg' => 'La matière produite ne peut pas dépasser les '.$this->kg($loop->received_kg).' reçus.']);
        }
        $loop->update(['status' => 'transformed', 'transformed_at' => now(), 'output_kg' => round($outputKg, 2), 'proof_path' => $proof->store('recycling-loops/proofs', 'local')]);
        $this->notifyBrand($loop, 'recycling_transformed', 'Votre matière recyclée est prête',
            $loop->reference.' : '.$this->kg($outputKg).' de '.$loop->outputMaterial?->name.' (rendement '.$loop->yieldRate().' %).');

        return $loop;
    }

    /** Le fournisseur livre la matière recyclée : elle devient un lot traçable chez la marque. */
    public function deliver(RecyclingLoop $loop): RecyclingLoop
    {
        $this->guard($loop, ['transformed'], 'La matière doit être produite avant d’être livrée.');
        DB::transaction(function () use ($loop) {
            $batch = SupplierBatch::create([
                'supplier_id' => $loop->supplier_id,
                'material_id' => $loop->output_material_id,
                'batch_code' => $this->traceability->nextBatchCode($loop->supplier),
                'quantity_kg' => $loop->output_kg,
                'origin_country' => $loop->supplier->country ?: 'Tunisie',
                'production_date' => now()->toDateString(),
                'status' => 'pending',
            ]);
            $loop->update(['status' => 'delivered', 'delivered_at' => now(), 'output_batch_id' => $batch->id]);
        });
        $loop->load('outputBatch');
        $this->notifyBrand($loop, 'recycling_delivered', 'Matière recyclée livrée', $loop->reference.' : lot '.$loop->outputBatch->batch_code.' en route. Confirmez la réception.');

        return $loop;
    }

    public function close(RecyclingLoop $loop): RecyclingLoop
    {
        $this->guard($loop, ['delivered'], 'La matière recyclée n’a pas encore été livrée.');
        DB::transaction(function () use ($loop) {
            $loop->outputBatch?->update(['received_at' => now()->toDateString(), 'status' => 'received']);
            $loop->update(['status' => 'closed', 'closed_at' => now()]);
        });
        $this->notifySupplier($loop, 'recycling_closed', 'Boucle fermée avec '.$loop->brand->name,
            $loop->reference.' : la marque a bien reçu '.$this->kg($loop->output_kg).' de matière recyclée. Merci !');

        return $loop;
    }

    /** Nom automatique d'un lot : nature + produit ou matière + nombre de pièces. Ex. « Défectueux · Winter Jacket 02 · 25 pièces ». */
    public function autoLabel(array $item): string
    {
        $what = ! empty($item['product_id']) ? Product::whereKey($item['product_id'])->value('name') : null;
        $what ??= ! empty($item['material_id']) ? Material::whereKey($item['material_id'])->value('name') : null;

        return collect([
            RecyclingLoopItem::SHORT_KINDS[$item['kind']] ?? 'Lot',
            $what ?? 'matière à préciser',
            ! empty($item['pieces']) ? $item['pieces'].' pièces' : null,
        ])->filter()->join(' · ');
    }

    public function estimate(RecyclingLoop $loop): array
    {
        return $this->estimator->estimate($loop);
    }

    private function guard(RecyclingLoop $loop, array $statuses, string $message): void
    {
        if (! in_array($loop->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function notifySupplier(RecyclingLoop $loop, string $type, string $title, string $message): void
    {
        $this->notifications->send($loop->supplier?->user_id, $type, $title, $message, route('supplier.recycling.show', $loop));
    }

    private function notifyBrand(RecyclingLoop $loop, string $type, string $title, string $message): void
    {
        $this->notifications->send($loop->brand?->user_id, $type, $title, $message, route('brand.recycling.show', $loop));
    }

    private function kg(float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', ' ').' kg';
    }
}
