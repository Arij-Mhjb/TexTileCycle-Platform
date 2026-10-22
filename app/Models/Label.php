<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Label extends Model
{
    protected $fillable = ['name', 'slug', 'organization', 'pillar', 'description', 'official_url', 'cost_hint', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(LabelStep::class)->orderBy('position');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function productRecommendations(): HasMany
    {
        return $this->hasMany(ProductLabelRecommendation::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
