<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'sku',
        'category',
        'description',
        'image_path',
        'status',
        'year',
        'repairable',
        'reusable',
        'recyclable',
        'environmental_score',
        'circularity_score',
        'animal_free_score',
        'traceability_score',
    ];

    protected function casts(): array
    {
        return [
            'repairable' => 'boolean',
            'reusable' => 'boolean',
            'recyclable' => 'boolean',
        ];
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)
            ->withPivot(['percentage', 'origin_country', 'certified'])
            ->withTimestamps();
    }

    public function detections(): HasMany
    {
        return $this->hasMany(Detection::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }

        $fallbacks = [
            'images/impact/responsible-production.jpg',
            'images/story/clothes-sorting.jpg',
            'images/story/community-sorting.jpg',
        ];

        return asset($fallbacks[abs(crc32($this->sku ?: (string) $this->id)) % count($fallbacks)]);
    }
}
