<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeInRequest extends Model
{
    use HasFactory;

    public const CONDITIONS = ['good' => 'Très bon', 'wearable' => 'Portable', 'minor_damage' => 'Petits défauts', 'damaged' => 'Endommagé'];

    protected $fillable = [
        'reference', 'user_id', 'product_id', 'product_name', 'condition', 'description', 'proposed_points', 'status',
        'evaluated_by', 'evaluation_note', 'evaluated_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['evaluated_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function itemName(): string
    {
        return $this->product?->name ?? $this->product_name ?? 'Article';
    }

    public function statusLabel(): string
    {
        return [
            'submitted' => 'Envoyée', 'evaluated' => 'Offre reçue', 'accepted' => 'Acceptée — à déposer',
            'declined' => 'Refusée', 'completed' => 'Terminée',
        ][$this->status] ?? $this->status;
    }
}
