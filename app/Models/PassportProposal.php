<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PassportProposal extends Model
{
    protected $fillable = [
        'scan_id', 'consumer_id', 'brand_id', 'product_id', 'status', 'proposed_data',
        'field_provenance', 'admin_reviewed_by', 'admin_reviewed_at', 'admin_note',
        'brand_reviewed_at', 'brand_note', 'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_data' => 'array',
            'field_provenance' => 'array',
            'admin_reviewed_at' => 'datetime',
            'brand_reviewed_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function scan(): BelongsTo { return $this->belongsTo(Scan::class); }
    public function consumer(): BelongsTo { return $this->belongsTo(User::class, 'consumer_id'); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function adminReviewer(): BelongsTo { return $this->belongsTo(User::class, 'admin_reviewed_by'); }
    public function events(): HasMany { return $this->hasMany(PassportEvent::class)->oldest(); }
    public function collectionPoints(): BelongsToMany { return $this->belongsToMany(CollectionPoint::class, 'passport_proposal_collection_point')->withTimestamps(); }

    public function isReady(): bool
    {
        return $this->status === 'ready_for_return' && $this->product_id !== null;
    }

    public function statusLabel(): string
    {
        return [
            'admin_pending' => 'En attente du contrôle TexTileCycle',
            'consumer_information_required' => 'Informations complémentaires demandées',
            'admin_rejected' => 'Proposition refusée',
            'brand_pending' => 'En attente de la marque',
            'brand_rejected' => 'Refusée par la marque — réexamen Admin',
            'ready_for_return' => 'Passeport contrôlé — prêt pour le retour',
        ][$this->status] ?? str($this->status)->headline();
    }
}
