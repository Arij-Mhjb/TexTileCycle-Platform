<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerClaim extends Model
{
    public const CATEGORIES = [
        'deposit' => 'Dépôt non reconnu', 'weight' => 'Poids contesté', 'return_rejected' => 'Retour refusé',
        'reward' => 'Points ou récompense', 'repair' => 'Réparation', 'trade_in' => 'Reprise',
        'marketplace' => 'Achat marketplace', 'privacy' => 'Données personnelles', 'other' => 'Autre',
    ];

    protected $fillable = [
        'user_id', 'product_return_id', 'repair_order_id', 'trade_in_request_id', 'reference', 'category',
        'subject', 'description', 'status', 'priority', 'admin_response', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array { return ['resolved_at' => 'datetime']; }

    public function consumer(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function productReturn(): BelongsTo { return $this->belongsTo(ProductReturn::class); }
    public function repairOrder(): BelongsTo { return $this->belongsTo(RepairOrder::class); }
    public function tradeIn(): BelongsTo { return $this->belongsTo(TradeInRequest::class, 'trade_in_request_id'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }

    public function statusLabel(): string
    {
        return ['open' => 'Ouverte', 'in_review' => 'En cours', 'resolved' => 'Résolue', 'rejected' => 'Rejetée'][$this->status] ?? $this->status;
    }
}
