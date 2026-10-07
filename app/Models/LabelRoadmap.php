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
        return ['scope_version' => 'integer', 'starts_on' => 'date', 'target_date' => 'date', 'label_snapshot' => 'array'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('snapshot')->withTrashed();
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RoadmapStep::class)->orderBy('position');
    }

    public function progress(): int
    {
        $steps = $this->steps;

        return $steps->isEmpty() ? 0 : (int) round(100 * $steps->where('status', 'approved')->count() / $steps->count());
    }
}
