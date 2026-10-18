<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id', 'supplier_id', 'reference', 'status', 'ordered_at', 'expected_at', 'received_at',
        'total_amount', 'currency', 'notes', 'sent_at', 'shipped_at', 'supplier_note',
    ];

    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'expected_at' => 'date', 'received_at' => 'date', 'sent_at' => 'datetime', 'shipped_at' => 'datetime', 'total_amount' => 'decimal:2'];
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

    public function statusLabel(): string
    {
        return [
            'draft' => 'Brouillon', 'ordered' => 'Commandée', 'sent' => 'Envoyée au fournisseur',
            'shipped' => 'Expédiée par le fournisseur', 'received' => 'Reçue', 'cancelled' => 'Annulée',
        ][$this->status] ?? str($this->status)->headline();
    }

    /** Le fournisseur peut encore déclarer une expédition via son lien. */
    public function acceptsShipment(): bool
    {
        return in_array($this->status, ['ordered', 'sent', 'shipped'], true);
    }

    /** Lien sécurisé (signé, valable 60 jours) envoyé au fournisseur : aucun compte nécessaire. */
    public function supplierPortalUrl(): string
    {
        return URL::temporarySignedRoute('supplier-portal.show', now()->addDays(60), ['purchaseOrder' => $this->id]);
    }
}
