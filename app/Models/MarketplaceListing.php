<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class MarketplaceListing extends Model
{
    use HasFactory;

    public const GRADES = ['A' => 'Comme neuf', 'B' => 'Très bon état', 'C' => 'Bon état'];
    public const TRY_ON_CATEGORIES = ['top' => 'Haut / veste', 'dress' => 'Robe / manteau', 'bottom' => 'Pantalon / jupe', 'none' => 'Essayage indisponible'];

    protected $fillable = [
        'product_id', 'brand_id', 'repair_order_id', 'title', 'description', 'condition_grade', 'price', 'currency',
        'status', 'published_at', 'sold_at', 'points_price', 'image_path', 'try_on_category',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'sold_at' => 'datetime', 'price' => 'decimal:2', 'points_price' => 'integer'];
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

    /** True when the listed item comes from this consumer's own return. */
    public function isFromConsumer(?User $user): bool
    {
        return $user !== null && $this->repairOrder !== null && (int) $this->repairOrder->user_id === (int) $user->id;
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ResaleOffer::class)->latest();
    }

    public function order(): HasOne
    {
        return $this->hasOne(MarketplaceOrder::class);
    }

    public function statusLabel(): string
    {
        return ['draft' => 'Brouillon', 'published' => 'En vente', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'archived' => 'Archivé'][$this->status] ?? $this->status;
    }

    public function gradeLabel(): string
    {
        return self::GRADES[$this->condition_grade] ?? $this->condition_grade;
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) return Storage::disk('public')->url($this->image_path);

        return $this->product?->image_url ?? asset('images/story/clothes-sorting.jpg');
    }

    public function canTryOn(): bool
    {
        return $this->try_on_category !== 'none' && (bool) $this->image_path;
    }
}
