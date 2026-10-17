<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id', 'purchase_order_id', 'material_id', 'batch_code', 'quantity_kg', 'origin_country',
        'production_date', 'received_at', 'status', 'verification_note', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['production_date' => 'date', 'received_at' => 'date', 'verified_at' => 'datetime', 'quantity_kg' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(MaterialOriginCertificate::class)->latest('issued_at');
    }

    public function productLinks(): HasMany
    {
        return $this->hasMany(ProductSupplierLink::class);
    }

    public function hasValidCertificate(): bool
    {
        return $this->certificates->contains(fn (MaterialOriginCertificate $certificate) => $certificate->isValid());
    }

    public function statusLabel(): string
    {
        return ['pending' => 'En attente', 'received' => 'Reçu', 'verified' => 'Vérifié', 'rejected' => 'Refusé'][$this->status] ?? str($this->status)->headline();
    }
}
