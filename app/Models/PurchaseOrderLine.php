<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ligne de bon de commande : une matière, une quantité commandée, un prix unitaire négocié.
 * Le fournisseur peut confirmer une quantité différente (contre-proposition) ;
 * chaque expédition crée un lot rattaché à la ligne.
 */
class PurchaseOrderLine extends Model
{
    protected $fillable = ['purchase_order_id', 'material_id', 'quantity_kg', 'confirmed_kg', 'unit_price'];

    protected function casts(): array
    {
        return ['quantity_kg' => 'decimal:2', 'confirmed_kg' => 'decimal:2', 'unit_price' => 'decimal:3'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(SupplierBatch::class);
    }

    /** Quantité due : la quantité confirmée par le fournisseur si elle a été acceptée, sinon la quantité commandée. */
    public function dueKg(): float
    {
        return (float) ($this->confirmed_kg ?? $this->quantity_kg);
    }

    public function shippedKg(): float
    {
        return (float) $this->batches->sum('quantity_kg');
    }

    public function receivedKg(): float
    {
        return (float) $this->batches->whereNotNull('received_at')->sum('quantity_kg');
    }

    public function remainingKg(): float
    {
        return max(0, round($this->dueKg() - $this->shippedKg(), 2));
    }

    /** Livrée « en totalité » à la tolérance d'usage près (−2 %). */
    public function isFullyShipped(): bool
    {
        return $this->shippedKg() >= $this->dueKg() * (1 - PurchaseOrder::UNDER_DELIVERY_TOLERANCE);
    }

    /** Quantité maximale encore expédiable (tolérance de sur-livraison +5 %). */
    public function maxShippableKg(): float
    {
        return max(0, round($this->dueKg() * (1 + PurchaseOrder::OVER_DELIVERY_TOLERANCE) - $this->shippedKg(), 2));
    }

    public function amount(): ?float
    {
        return $this->unit_price !== null ? round($this->dueKg() * (float) $this->unit_price, 2) : null;
    }

    public function shippedPercent(): int
    {
        return $this->dueKg() > 0 ? (int) min(100, round($this->shippedKg() / $this->dueKg() * 100)) : 0;
    }
}
