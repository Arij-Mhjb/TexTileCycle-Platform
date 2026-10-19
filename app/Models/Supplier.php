<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Le fournisseur a activé son accès : c'est lui qui déclare ses livraisons (lots) et ses certificats.
     * La marque consulte, réceptionne et relie les lots à ses produits, mais ne saisit plus à sa place.
     */
    public function managesOwnLots(): bool
    {
        return $this->user !== null && ! $this->user->must_change_password;
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SupplierInvitation::class);
    }

    public function latestInvitation(): HasOne
    {
        return $this->hasOne(SupplierInvitation::class)->latestOfMany();
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
        return ['pending' => 'En attente d’activation', 'active' => 'Actif', 'under_review' => 'En revue', 'suspended' => 'Suspendu'][$this->status] ?? str($this->status)->headline();
    }

    public function statusTone(): string
    {
        return ['active' => 'good', 'suspended' => 'bad'][$this->status] ?? 'warn';
    }

    /**
     * Statut automatique : « En attente d'activation » tant que le fournisseur n'a pas ouvert son compte,
     * « Actif » dès qu'il l'a activé. Une suspension décidée par la marque n'est jamais écrasée.
     */
    public function syncAccountStatus(): void
    {
        if ($this->isSuspended()) {
            return;
        }
        $this->loadMissing('user');
        $this->update(['status' => $this->managesOwnLots() ? 'active' : 'pending']);
    }

    /**
     * Certifications prouvées : déduites des certificats valides (vérifiés, non expirés) de ses lots,
     * au lieu d'un champ déclaratif saisi à la main.
     */
    public function certificationsLabel(): ?string
    {
        $types = MaterialOriginCertificate::whereHas('batch', fn ($query) => $query->where('supplier_id', $this->id))->get()
            ->filter(fn (MaterialOriginCertificate $certificate) => $certificate->isValid())
            ->pluck('type')->filter()->unique()
            ->map(fn (string $type) => ['origin' => 'Certificat d’origine', 'other' => 'Autre certificat'][$type] ?? $type);

        return $types->isEmpty() ? null : $types->sort()->join(', ');
    }
}
