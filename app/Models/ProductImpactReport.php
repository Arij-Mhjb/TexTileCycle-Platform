<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImpactReport extends Model
{
    protected $fillable = [
        'product_id', 'brand_id', 'generated_by', 'season', 'product_weight_kg', 'carbon_kg', 'water_l', 'energy_kwh',
        'waste_kg', 'avoided_waste_kg', 'circularity_bonus', 'impact_score', 'grade', 'status', 'assessment_id',
        'validated_by', 'validated_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'product_weight_kg' => 'decimal:3', 'carbon_kg' => 'decimal:3', 'water_l' => 'decimal:2',
            'energy_kwh' => 'decimal:3', 'waste_kg' => 'decimal:3', 'avoided_waste_kg' => 'decimal:3',
            'validated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function carbonFootprints(): HasMany
    {
        return $this->hasMany(CarbonFootprint::class);
    }

    public function waterFootprints(): HasMany
    {
        return $this->hasMany(WaterFootprint::class);
    }
}
