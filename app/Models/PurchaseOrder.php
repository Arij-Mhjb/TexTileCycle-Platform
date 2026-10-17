<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id', 'supplier_id', 'reference', 'status', 'ordered_at', 'expected_at', 'received_at',
        'total_amount', 'currency', 'notes',
    ];

    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'expected_at' => 'date', 'received_at' => 'date', 'total_amount' => 'decimal:2'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(SupplierBatch::class);
    }

    public function statusLabel(): string
    {
        return ['draft' => 'Brouillon', 'ordered' => 'Commandée', 'received' => 'Reçue', 'cancelled' => 'Annulée'][$this->status] ?? str($this->status)->headline();
    }
}
