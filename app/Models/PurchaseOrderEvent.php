<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Journal d'une commande : qui a fait quoi et quand (marque, fournisseur ou système). */
class PurchaseOrderEvent extends Model
{
    protected $fillable = ['purchase_order_id', 'user_id', 'actor', 'type', 'message'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
