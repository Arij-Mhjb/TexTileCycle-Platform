<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceListing extends Model
{
    public const GRADES = ['A' => 'Comme neuf', 'B' => 'Très bon état', 'C' => 'Bon état'];

    protected $fillable = [
        'product_id', 'brand_id', 'repair_order_id', 'title', 'description', 'condition_grade', 'price', 'currency',
        'status', 'published_at', 'sold_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'sold_at' => 'datetime', 'price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ResaleOffer::class)->latest();
    }

    public function statusLabel(): string
    {
        return ['draft' => 'Brouillon', 'published' => 'En vente', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'archived' => 'Archivé'][$this->status] ?? $this->status;
    }

    public function gradeLabel(): string
    {
        return self::GRADES[$this->condition_grade] ?? $this->condition_grade;
    }
}
