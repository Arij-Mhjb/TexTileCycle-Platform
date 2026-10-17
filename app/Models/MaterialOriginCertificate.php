<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialOriginCertificate extends Model
{
    use HasFactory;

    public const TYPES = [
        'GOTS' => 'GOTS (coton biologique)',
        'OEKO-TEX' => 'OEKO-TEX Standard 100',
        'GRS' => 'Global Recycled Standard',
        'RWS' => 'Responsible Wool Standard',
        'FSC' => 'FSC (fibres cellulosiques)',
        'origin' => 'Certificat d’origine',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'supplier_batch_id', 'type', 'certificate_number', 'issuer', 'issued_at', 'expires_at',
        'document_url', 'ai_extraction', 'status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['ai_extraction' => 'array', 'issued_at' => 'date', 'expires_at' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SupplierBatch::class, 'supplier_batch_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'verified' && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        if ($this->status === 'verified' && $this->isExpired()) {
            return 'Expiré';
        }

        return ['pending' => 'À vérifier', 'verified' => 'Vérifié', 'rejected' => 'Refusé'][$this->status] ?? str($this->status)->headline();
    }
}
