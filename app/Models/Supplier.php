<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id', 'user_id', 'name', 'contact_name', 'email', 'phone', 'country', 'city', 'address',
        'certifications', 'status', 'compliance_score', 'ai_risk', 'notes',
    ];

    protected function casts(): array
    {
        return ['ai_risk' => 'array'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** Compte TexTileCycle du fournisseur (rôle « supplier »), s'il a un accès. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class)->latest();
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

    /** On ne supprime jamais une preuve : suppression seulement sans lot vérifié ni relié à un produit. */
    public function canBeDeleted(): bool
    {
        return ! $this->batches()->where(fn ($query) => $query->where('status', 'verified')->orWhereHas('productLinks'))->exists();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function statusLabel(): string
    {
        return ['active' => 'Actif', 'under_review' => 'En revue', 'suspended' => 'Suspendu'][$this->status] ?? str($this->status)->headline();
    }
}
