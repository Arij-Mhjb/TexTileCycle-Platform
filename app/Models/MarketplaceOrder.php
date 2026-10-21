<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrder extends Model
{
    protected $fillable = [
        'reference', 'marketplace_listing_id', 'resale_offer_id', 'buyer_id', 'collection_point_id',
        'total_amount', 'currency', 'fulfilment_method', 'payment_method', 'delivery_address',
        'status', 'checked_out_at', 'received_at',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2', 'checked_out_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function listing(): BelongsTo { return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id'); }
    public function offer(): BelongsTo { return $this->belongsTo(ResaleOffer::class, 'resale_offer_id'); }
    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'buyer_id'); }
    public function collectionPoint(): BelongsTo { return $this->belongsTo(CollectionPoint::class); }

    public function statusLabel(): string
    {
        return [
            'awaiting_checkout' => 'À finaliser', 'confirmed' => 'Confirmée', 'ready' => 'Prête à retirer',
            'shipped' => 'Expédiée', 'completed' => 'Reçue', 'cancelled' => 'Annulée',
        ][$this->status] ?? $this->status;
    }
}
