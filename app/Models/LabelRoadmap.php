<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelRoadmap extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['status' => 'active', 'scope_version' => 1];

    protected function casts(): array
    {
        return ['scope_version' => 'integer', 'starts_on' => 'date', 'target_date' => 'date', 'label_snapshot' => 'array',
            'specification' => 'array', 'impact_summary' => 'array', 'ai_assessment_snapshot' => 'array', 'certification_estimate' => 'array', 'assigned_at' => 'datetime'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(ProductLabelRecommendation::class, 'product_label_recommendation_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('snapshot')->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(BrandMember::class, 'brand_member_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RoadmapStep::class)->orderBy('position');
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function progress(): int
    {
        $steps = $this->steps;

        return $steps->isEmpty() ? 0 : (int) round(100 * $steps->where('status', 'approved')->count() / $steps->count());
    }
}
