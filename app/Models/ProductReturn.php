<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'scan_id', 'product_id', 'collection_point_id', 'reference', 'status', 'action',
        'condition', 'qr_token', 'weight_kg', 'confirmed_weight_kg', 'points_awarded',
        'returned_at', 'deposited_at', 'completed_at', 'cancelled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'deposited_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'weight_kg' => 'decimal:2',
            'confirmed_weight_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function scan(): BelongsTo { return $this->belongsTo(Scan::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function collectionPoint(): BelongsTo { return $this->belongsTo(CollectionPoint::class); }
    public function treatmentResult(): HasOne { return $this->hasOne(TreatmentResult::class); }
    public function rewardTransaction(): HasOne { return $this->hasOne(RewardTransaction::class); }
    public function statusHistory(): HasMany { return $this->hasMany(ReturnStatusHistory::class)->oldest(); }

    public function canConsumerEdit(): bool
    {
        return in_array($this->status, ['declared', 'dropoff_expected'], true);
    }

    public function statusLabel(): string
    {
        return [
            'declared' => 'Déclaré',
            'dropoff_expected' => 'Dépôt attendu',
            'received' => 'Reçu',
            'inspected' => 'Contrôlé',
            'sorting' => 'Contrôlé',
            'routed' => 'Orienté',
            'completed' => 'Terminé',
            'processed' => 'Terminé',
            'rejected' => 'Refusé',
            'cancelled' => 'Annulé',
        ][$this->status] ?? str($this->status)->headline();
    }
}
