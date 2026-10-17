<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepairOrder extends Model
{
    public const STATUSES = [
        'requested' => 'Demandée',
        'diagnosed' => 'Diagnostiquée',
        'in_repair' => 'En réparation',
        'repaired' => 'Reconditionnée',
        'listed' => 'En vente',
        'closed' => 'Clôturée',
        'cancelled' => 'Annulée',
    ];

    public const DECISIONS = ['repair' => 'Réparer et rendre', 'resale' => 'Reconditionner et revendre', 'recycle' => 'Recycler'];

    protected $fillable = [
        'reference', 'user_id', 'product_return_id', 'product_id', 'repair_center_id', 'issue_description', 'decision',
        'status', 'estimated_cost', 'final_cost', 'reward_points', 'admin_note', 'ai_diagnosis', 'diagnosed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['ai_diagnosis' => 'array', 'diagnosed_at' => 'datetime', 'completed_at' => 'datetime', 'estimated_cost' => 'decimal:2', 'final_cost' => 'decimal:2'];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function repairCenter(): BelongsTo
    {
        return $this->belongsTo(RepairCenter::class);
    }

    public function refurbishments(): HasMany
    {
        return $this->hasMany(Refurbishment::class)->latest();
    }

    public function listing(): HasOne
    {
        return $this->hasOne(MarketplaceListing::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? str($this->status)->headline();
    }

    public function decisionLabel(): ?string
    {
        return $this->decision ? (self::DECISIONS[$this->decision] ?? $this->decision) : null;
    }
}
