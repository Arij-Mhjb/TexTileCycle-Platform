<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cycle de vie d'une commande fournisseur, comme dans un vrai service achats :
 *
 *   commandée ──► acceptée ──► livraison partielle ──► entièrement expédiée ──► reçue
 *        │            ▲                 │
 *        ├─► contre-proposition ─┘ (la marque accepte)        └─► soldée avec reliquat
 *        └─► refusée / annulée
 *
 * Chaque étape est tracée dans le journal de la commande (purchase_order_events).
 */
class PurchaseOrderService
{
    public function __construct(private readonly SupplierTraceabilityService $traceability) {}

    /**
     * @param  array{ordered_at: string, expected_at?: ?string, notes?: ?string}  $header
     * @param  array<int, array{material_id: int, quantity_kg: float|string, unit_price?: float|string|null}>  $lines
     */
    public function create(Supplier $supplier, array $header, array $lines, User $by): PurchaseOrder
    {
        if ($supplier->isSuspended()) {
            throw ValidationException::withMessages(['supplier' => 'Ce fournisseur est suspendu : réactivez-le pour lui passer une commande.']);
        }

        return DB::transaction(function () use ($supplier, $header, $lines, $by) {
            $order = $supplier->purchaseOrders()->create([
                'brand_id' => $supplier->brand_id,
                'reference' => $this->traceability->nextPurchaseOrderReference(),
                'status' => 'ordered',
                'ordered_at' => $header['ordered_at'],
                'expected_at' => $header['expected_at'] ?? null,
                'notes' => $header['notes'] ?? null,
            ]);
            foreach ($lines as $line) {
                $order->lines()->create([
                    'material_id' => $line['material_id'],
                    'quantity_kg' => $line['quantity_kg'],
                    'unit_price' => ($line['unit_price'] ?? '') === '' ? null : $line['unit_price'],
                ]);
            }
            $order->load('lines.batches', 'lines.material');
            $order->update(['total_amount' => $order->linesAmount()]);
            $this->log($order, 'brand', 'created', 'Commande créée : '.$this->describeLines($order).'.', $by);

            return $order;
        });
    }

    /**
     * Réponse du fournisseur : accepter, refuser, ou contre-proposer
     * (quantités confirmées ligne par ligne et/ou nouvelle date de livraison).
     *
     * @param  array{decline_reason?: ?string, confirmed?: array<int|string, float|string|null>, proposed_expected_at?: ?string, supplier_note?: ?string}  $data
     */
    public function supplierAnswer(PurchaseOrder $order, string $decision, array $data, User $by): PurchaseOrder
    {
        $order->load('lines.material', 'lines.batches', 'brand', 'supplier');
        $note = $data['supplier_note'] ?? null;

        if ($decision === 'decline') {
            $order->update(['status' => 'declined', 'declined_at' => now(), 'decline_reason' => $data['decline_reason'], 'supplier_note' => $note]);
            $this->log($order, 'supplier', 'declined', 'Commande refusée : « '.$data['decline_reason'].' »', $by);
            $this->notifyBrand($order, 'supplier_order_declined', $order->supplier->name.' a refusé la commande', 'Commande '.$order->reference.' refusée : '.$data['decline_reason']);

            return $order;
        }

        if ($decision === 'accept') {
            $order->update(['status' => 'accepted', 'accepted_at' => now(), 'supplier_note' => $note]);
            $this->log($order, 'supplier', 'accepted', 'Commande acceptée telle quelle'.($note ? ' : « '.$note.' »' : '.'), $by);
            $this->notifyBrand($order, 'supplier_order_accepted', $order->supplier->name.' a accepté la commande', 'Commande '.$order->reference.' acceptée.');

            return $order;
        }

        // Contre-proposition.
        $changes = [];
        foreach ($order->lines as $line) {
            $confirmed = $data['confirmed'][$line->id] ?? null;
            $confirmed = $confirmed === null || $confirmed === '' ? (float) $line->quantity_kg : round((float) $confirmed, 2);
            if (abs($confirmed - (float) $line->quantity_kg) >= 0.01) {
                $changes[] = ($line->material?->name ?? 'Matière').' : '.$this->kg($confirmed).' au lieu de '.$this->kg((float) $line->quantity_kg);
            }
            $line->update(['confirmed_kg' => $confirmed]);
        }
        $proposedDate = $data['proposed_expected_at'] ?? null;
        if ($proposedDate && (! $order->expected_at || $order->expected_at->toDateString() !== $proposedDate)) {
            $changes[] = 'livraison le '.Carbon::parse($proposedDate)->format('d/m/Y').($order->expected_at ? ' au lieu du '.$order->expected_at->format('d/m/Y') : '');
        } else {
            $proposedDate = null;
        }

        if ($changes === []) {
            throw ValidationException::withMessages(['decision' => 'Votre contre-proposition ne change rien : modifiez une quantité ou la date, ou acceptez la commande telle quelle.']);
        }
        if ($order->lines->every(fn (PurchaseOrderLine $line) => (float) $line->confirmed_kg <= 0)) {
            throw ValidationException::withMessages(['decision' => 'Vous ne confirmez aucune quantité : refusez plutôt la commande en indiquant la raison.']);
        }

        $order->update(['status' => 'countered', 'countered_at' => now(), 'proposed_expected_at' => $proposedDate, 'supplier_note' => $note]);
        $message = 'Contre-proposition : '.implode(' ; ', $changes).($note ? '. « '.$note.' »' : '.');
        $this->log($order, 'supplier', 'countered', $message, $by);
        $this->notifyBrand($order, 'supplier_order_countered', $order->supplier->name.' propose des modifications', 'Commande '.$order->reference.' — '.$message);

        return $order;
    }

    /** La marque accepte ou refuse la contre-proposition du fournisseur. */
    public function decideCounter(PurchaseOrder $order, bool $accept, ?string $reason, User $by): PurchaseOrder
    {
        $order->load('lines.batches', 'lines.material');
        if (! $order->awaitsBrandDecision()) {
            throw ValidationException::withMessages(['decision' => 'Aucune contre-proposition en attente sur cette commande.']);
        }

        if ($accept) {
            $order->update([
                'status' => 'accepted', 'accepted_at' => now(),
                'expected_at' => $order->proposed_expected_at ?? $order->expected_at,
                'total_amount' => $order->linesAmount(),
            ]);
            $this->log($order, 'brand', 'counter_accepted', 'Contre-proposition acceptée : la commande porte désormais sur '.$this->describeLines($order).'.', $by);
        } else {
            $order->update(['status' => 'cancelled', 'close_reason' => $reason]);
            $this->log($order, 'brand', 'counter_rejected', 'Contre-proposition refusée, commande annulée'.($reason ? ' : « '.$reason.' »' : '.'), $by);
        }

        return $order;
    }

    /**
     * Le fournisseur expédie une partie (ou la totalité) d'une ligne : un lot est créé,
     * avec le certificat lu par l'IA. Les quantités sont contrôlées avec la tolérance de sur-livraison.
     *
     * @param  array{quantity_kg: float|string, origin_country: string, production_date?: ?string, supplier_note?: ?string}  $data
     * @return array{batch: SupplierBatch, message: string, recognized: bool}
     */
    public function ship(PurchaseOrder $order, PurchaseOrderLine $line, array $data, UploadedFile $certificate, User $by): array
    {
        $order->load('lines.batches', 'lines.material', 'supplier', 'brand');
        $line = $order->lines->firstWhere('id', $line->id);
        if (! $line || ! $order->acceptsShipment()) {
            throw ValidationException::withMessages(['purchase_order_line_id' => 'Cette ligne n’est pas expédiable.']);
        }
        $quantity = round((float) $data['quantity_kg'], 2);
        if ($quantity > $line->maxShippableKg()) {
            throw ValidationException::withMessages(['quantity_kg' => 'Quantité trop élevée : il reste '.$this->kg($line->remainingKg()).' à livrer sur cette ligne (tolérance +'.(int) (PurchaseOrder::OVER_DELIVERY_TOLERANCE * 100).' % : '.$this->kg($line->maxShippableKg()).' maximum).']);
        }

        DB::beginTransaction();
        try {
            $batch = $order->supplier->batches()->create([
                'purchase_order_id' => $order->id,
                'purchase_order_line_id' => $line->id,
                'batch_code' => $this->traceability->nextBatchCode($order->supplier),
                'material_id' => $line->material_id,
                'quantity_kg' => $quantity,
                'origin_country' => $data['origin_country'],
                'production_date' => $data['production_date'] ?? null,
                'status' => 'pending',
            ]);
            // Toute image est acceptée : si ce n'est pas un certificat, la livraison est enregistrée
            // mais le lot n'a pas encore de preuve valable (la marque est prévenue).
            $outcome = $this->traceability->addCertificateFromPhoto($batch, $certificate);
            $order->forceFill(['shipped_at' => $order->shipped_at ?? now(), 'supplier_note' => $data['supplier_note'] ?? $order->supplier_note])->save();
            $this->refreshStatus($order);
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        $line->unsetRelation('batches');
        $progress = $this->kg($line->shippedKg()).' / '.$this->kg($line->dueKg());
        $this->log($order, 'supplier', 'shipped', 'Livraison expédiée : lot '.$batch->batch_code.', '.$this->kg($quantity).' de '.($line->material?->name ?? 'matière').' ('.$progress.' sur la ligne).', $by);
        $this->notifyBrand($order, 'supplier_shipment', 'Livraison en route de '.$order->supplier->name,
            'Commande '.$order->reference.' : lot '.$batch->batch_code.' ('.$this->kg($quantity).'). Confirmez la réception à l’arrivée.'
            .($outcome['recognized'] ? '' : ' Attention : l’image jointe n’est pas un certificat ('.$outcome['description'].').'));
        if (! $outcome['recognized']) {
            $this->log($order, 'system', 'certificate_missing', 'L’image jointe au lot '.$batch->batch_code.' n’est pas un certificat : '.$outcome['description'], null);
        }

        return ['batch' => $batch, 'message' => $outcome['message'], 'recognized' => $outcome['recognized']];
    }

    /** La marque confirme l'arrivée d'une livraison (un lot). */
    public function receive(SupplierBatch $batch, User $by): PurchaseOrder
    {
        $order = $batch->purchaseOrder;
        if ($batch->received_at) {
            throw ValidationException::withMessages(['batch' => 'Cette livraison est déjà réceptionnée.']);
        }
        $batch->update(['received_at' => now()->toDateString(), 'status' => $batch->status === 'pending' ? 'received' : $batch->status]);
        $this->refreshStatus($order);
        $this->log($order, 'brand', 'received', 'Livraison réceptionnée : lot '.$batch->batch_code.' ('.$this->kg((float) $batch->quantity_kg).').', $by);

        return $order;
    }

    /** Solder la commande : la marque renonce aux quantités restantes. */
    public function closeShort(PurchaseOrder $order, string $reason, User $by): PurchaseOrder
    {
        $order->load('lines.batches', 'lines.material');
        if (! $order->canCloseShort()) {
            throw ValidationException::withMessages(['close_reason' => 'Seule une commande partiellement livrée peut être soldée.']);
        }
        $remaining = $order->lines->sum(fn (PurchaseOrderLine $line) => $line->remainingKg());
        $order->update(['closed_short_at' => now(), 'close_reason' => $reason]);
        $this->refreshStatus($order);
        $this->log($order, 'brand', 'closed_short', 'Commande soldée : '.$this->kg($remaining).' de reliquat abandonné(s). « '.$reason.' »', $by);

        return $order;
    }

    public function cancel(PurchaseOrder $order, ?string $reason, User $by): PurchaseOrder
    {
        if (! $order->isCancellable()) {
            throw ValidationException::withMessages(['status' => 'Une commande déjà expédiée ne peut plus être annulée : soldez-la plutôt.']);
        }
        $order->update(['status' => 'cancelled', 'close_reason' => $reason]);
        $this->log($order, 'brand', 'cancelled', 'Commande annulée par la marque'.($reason ? ' : « '.$reason.' »' : '.'), $by);

        return $order;
    }

    /** Recalcule l'état d'avancement à partir des lignes et des lots. */
    public function refreshStatus(PurchaseOrder $order): void
    {
        if (! in_array($order->status, ['accepted', 'partially_shipped', 'shipped'], true)) {
            return;
        }
        $order->load('lines.batches');
        $batches = $order->lines->flatMap->batches;
        $allShipped = $order->closed_short_at !== null || $order->lines->every(fn (PurchaseOrderLine $line) => $line->isFullyShipped());
        $allReceived = $batches->isNotEmpty() && $batches->every(fn (SupplierBatch $batch) => $batch->received_at !== null);

        $status = match (true) {
            $allShipped && $allReceived => 'received',
            $allShipped => 'shipped',
            $batches->isNotEmpty() => 'partially_shipped',
            default => 'accepted',
        };
        $order->update(['status' => $status, 'received_at' => $status === 'received' ? ($order->received_at ?? now()) : null]);
    }

    /** @param  \DateTimeInterface|null  $at  date de l'événement (reprise d'historique), maintenant par défaut */
    public function log(PurchaseOrder $order, string $actor, string $type, string $message, ?User $by = null, ?\DateTimeInterface $at = null): void
    {
        $event = $order->events()->make(['actor' => $actor, 'type' => $type, 'message' => $message, 'user_id' => $by?->id]);
        if ($at) {
            $event->forceFill(['created_at' => $at, 'updated_at' => $at]);
        }
        $event->save();
    }

    private function notifyBrand(PurchaseOrder $order, string $type, string $title, string $message): void
    {
        if ($userId = $order->brand?->user_id) {
            BrandNotification::create(['user_id' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, 'url' => route('brand.purchase-orders.show', $order)]);
        }
    }

    private function describeLines(PurchaseOrder $order): string
    {
        return $order->lines->map(fn (PurchaseOrderLine $line) => $this->kg($line->dueKg()).' de '.($line->material?->name ?? 'matière'))->join(', ', ' et ');
    }

    private function kg(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',').' kg';
    }
}
