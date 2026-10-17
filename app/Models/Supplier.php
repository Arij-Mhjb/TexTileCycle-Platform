<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Supplier extends Model
{
    protected $fillable = [
        'brand_id', 'name', 'contact_name', 'email', 'phone', 'country', 'city', 'address',
        'certifications', 'status', 'compliance_score', 'notes',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(SupplierBatch::class)->latest();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->latest('ordered_at');
    }

    public function certificates(): HasManyThrough
    {
        return $this->hasManyThrough(MaterialOriginCertificate::class, SupplierBatch::class);
    }

    public function statusLabel(): string
    {
        return ['active' => 'Actif', 'under_review' => 'En revue', 'suspended' => 'Suspendu'][$this->status] ?? str($this->status)->headline();
    }
}
