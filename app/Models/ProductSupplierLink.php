<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSupplierLink extends Model
{
    protected $fillable = [
        'product_id', 'supplier_batch_id', 'component', 'percentage', 'conformity_status', 'conformity_note', 'checked_at',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime', 'percentage' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SupplierBatch::class, 'supplier_batch_id');
    }

    public function conformityLabel(): string
    {
        return ['unchecked' => 'Non contrôlé', 'conform' => 'Conforme', 'mismatch' => 'Écart détecté'][$this->conformity_status] ?? $this->conformity_status;
    }
}
