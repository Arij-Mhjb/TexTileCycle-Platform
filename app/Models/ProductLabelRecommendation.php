<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLabelRecommendation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'readiness_score' => 'integer', 'gaps' => 'array', 'target_composition' => 'array',
            'next_steps' => 'array', 'environmental_impacts' => 'array', 'reputation_impacts' => 'array',
            'source_snapshot' => 'array', 'analysed_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    public function roadmaps(): HasMany
    {
        return $this->hasMany(LabelRoadmap::class);
    }
}
