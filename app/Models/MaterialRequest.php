<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends Model
{
    protected $fillable = [
        'brand_id', 'title', 'material_name', 'minimum_percentage', 'categories',
        'accepted_conditions', 'quantity_kg', 'remaining_kg', 'city', 'dropoff_address',
        'reward_points', 'status', 'expires_at', 'roadmap_step_id', 'roadmap_scope_version',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'accepted_conditions' => 'array',
            'expires_at' => 'datetime',
            'minimum_percentage' => 'decimal:2',
            'quantity_kg' => 'decimal:2',
            'remaining_kg' => 'decimal:2',
        ];
    }

    public function roadmapStep(): BelongsTo
    {
        return $this->belongsTo(RoadmapStep::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MaterialMatch::class);
    }
}
