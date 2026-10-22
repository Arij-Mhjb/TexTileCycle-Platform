<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lien d'activation d'un compte fournisseur : à usage unique, valable 72 h,
 * annulé dès qu'une nouvelle invitation est envoyée.
 */
class SupplierInvitation extends Model
{
    public const VALIDITY_HOURS = 72;

    protected $fillable = ['supplier_id', 'user_id', 'invited_by', 'token_hash', 'expires_at', 'used_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return static::with(['supplier.brand', 'user'])->where('token_hash', static::hashToken($token))->first();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->revoked_at === null && $this->expires_at->isFuture();
    }

    /** Pourquoi le lien ne marche plus (affiché au fournisseur). */
    public function unusableReason(): string
    {
        return match (true) {
            $this->used_at !== null => 'Ce lien a déjà été utilisé : connectez-vous avec votre mot de passe.',
            $this->revoked_at !== null => 'Ce lien a été remplacé par une invitation plus récente.',
            default => 'Ce lien a expiré.',
        };
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->used_at !== null => 'Activée le '.$this->used_at->format('d/m/Y à H:i'),
            $this->revoked_at !== null => 'Remplacée',
            $this->expires_at->isPast() => 'Expirée le '.$this->expires_at->format('d/m/Y à H:i'),
            default => 'En attente · expire le '.$this->expires_at->format('d/m/Y à H:i'),
        };
    }
}
