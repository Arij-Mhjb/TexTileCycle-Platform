<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Brand extends Model
{
    protected $attributes = ['onboarding_status' => 'approved'];

    protected $fillable = ['user_id', 'name', 'slug', 'logo_path', 'description', 'website', 'country', 'commitments', 'contact_email', 'is_active', 'onboarding_status'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(BrandApplication::class);
    }

    public function approvedLabels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class)->withTimestamps();
    }

    public function roadmaps(): HasMany
    {
        return $this->hasMany(LabelRoadmap::class);
    }

    public function productLabelRecommendations(): HasMany
    {
        return $this->hasMany(ProductLabelRecommendation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
