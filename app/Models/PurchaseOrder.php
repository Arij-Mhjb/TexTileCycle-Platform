<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id', 'supplier_id', 'reference', 'status', 'ordered_at', 'expected_at', 'received_at',
        'total_amount', 'currency', 'notes', 'sent_at', 'accepted_at', 'declined_at', 'shipped_at', 'supplier_note', 'decline_reason',
        'proposed_expected_at', 'countered_at', 'closed_short_at', 'close_reason',
    ];

    /** Tolérances d'usage dans le textile : une ligne est soldée à −2 %, on refuse au-delà de +5 %. */
    public const UNDER_DELIVERY_TOLERANCE = 0.02;

    public const OVER_DELIVERY_TOLERANCE = 0.05;

    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'expected_at' => 'date', 'received_at' => 'date', 'sent_at' => 'datetime', 'accepted_at' => 'datetime', 'declined_at' => 'datetime', 'shipped_at' => 'datetime', 'total_amount' => 'decimal:2',
            'proposed_expected_at' => 'date', 'countered_at' => 'datetime', 'closed_short_at' => 'datetime'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(SupplierBatch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PurchaseOrderEvent::class)->latest()->latest('id');
    }

    public function dueKg(): float
    {
        return (float) $this->lines->sum(fn (PurchaseOrderLine $line) => $line->dueKg());
    }

    public function shippedKg(): float
    {
        return (float) $this->lines->sum(fn (PurchaseOrderLine $line) => $line->shippedKg());
    }

    public function receivedKg(): float
    {
        return (float) $this->lines->sum(fn (PurchaseOrderLine $line) => $line->receivedKg());
    }

    public function shippedPercent(): int
    {
        return $this->dueKg() > 0 ? (int) min(100, round($this->shippedKg() / $this->dueKg() * 100)) : 0;
    }

    public function receivedPercent(): int
    {
        return $this->dueKg() > 0 ? (int) min(100, round($this->receivedKg() / $this->dueKg() * 100)) : 0;
    }

    /** Montant calculé à partir des lignes (quantité due × prix unitaire). */
    public function linesAmount(): ?float
    {
        $amounts = $this->lines->map(fn (PurchaseOrderLine $line) => $line->amount())->filter(fn ($amount) => $amount !== null);

        return $amounts->isEmpty() ? null : round($amounts->sum(), 2);
    }

    /** En retard : date de livraison dépassée alors que tout n'est pas expédié. */
    public function isLate(): bool
    {
        return $this->expected_at !== null && $this->expected_at->isPast() && ! $this->expected_at->isToday()
            && in_array($this->status, ['accepted', 'partially_shipped'], true);
    }

    /** Libellé court pour les chiffres clés. */
    public function shortStatusLabel(): string
    {
        return [
            'ordered' => 'En attente', 'sent' => 'En attente', 'countered' => 'À décider', 'accepted' => 'Acceptée',
            'partially_shipped' => 'En livraison', 'shipped' => 'Expédiée', 'received' => $this->closed_short_at ? 'Soldée' : 'Reçue',
            'declined' => 'Refusée', 'cancelled' => 'Annulée',
        ][$this->status] ?? $this->statusLabel();
    }

    public function statusTone(): string
    {
        return ['received' => 'good', 'accepted' => 'good', 'cancelled' => 'bad', 'declined' => 'bad', 'countered' => 'warn', 'partially_shipped' => 'warn', 'shipped' => 'warn'][$this->status] ?? 'suppliers';
    }

    public function statusLabel(): string
    {
        return [
            'draft' => 'Brouillon', 'ordered' => 'En attente de réponse du fournisseur', 'sent' => 'En attente de réponse du fournisseur',
            'accepted' => 'Acceptée par le fournisseur', 'declined' => 'Refusée par le fournisseur',
            'countered' => 'Contre-proposition à valider', 'partially_shipped' => 'Livraison partielle',
            'shipped' => 'Entièrement expédiée', 'received' => $this->closed_short_at ? 'Soldée avec reliquat' : 'Reçue', 'cancelled' => 'Annulée',
        ][$this->status] ?? str($this->status)->headline();
    }

    /** Le fournisseur doit encore accepter ou refuser la commande. */
    public function awaitsSupplierAnswer(): bool
    {
        return in_array($this->status, ['ordered', 'sent'], true);
    }

    /** Le fournisseur a fait une contre-proposition : la marque doit l'accepter ou la refuser. */
    public function awaitsBrandDecision(): bool
    {
        return $this->status === 'countered';
    }

    /** Commande acceptée : le fournisseur peut expédier, en une ou plusieurs livraisons. */
    public function acceptsShipment(): bool
    {
        return in_array($this->status, ['accepted', 'partially_shipped'], true);
    }

    /** Commande encore vivante (pas reçue, refusée ni annulée). */
    public function isOpen(): bool
    {
        return in_array($this->status, ['ordered', 'sent', 'countered', 'accepted', 'partially_shipped', 'shipped'], true);
    }

    /** Annulable tant que rien n'a été expédié. */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['ordered', 'sent', 'countered', 'accepted'], true);
    }

    /** Soldable avec reliquat : une partie a été livrée, la marque renonce au reste. */
    public function canCloseShort(): bool
    {
        return $this->status === 'partially_shipped';
    }
}
