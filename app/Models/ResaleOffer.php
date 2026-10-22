<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResaleOffer extends Model
{
    protected $fillable = ['marketplace_listing_id', 'user_id', 'offered_price', 'message', 'status', 'responded_at'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime', 'offered_price' => 'decimal:2'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusLabel(): string
    {
        return ['pending' => 'En attente', 'accepted' => 'Acceptée', 'declined' => 'Refusée', 'cancelled' => 'Annulée'][$this->status] ?? $this->status;
    }
}
