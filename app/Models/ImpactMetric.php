<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpactMetric extends Model
{
    protected $fillable = ['material_id', 'impact_category_id', 'value_per_kg', 'source'];

    protected function casts(): array
    {
        return ['value_per_kg' => 'decimal:4'];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ImpactCategory::class, 'impact_category_id');
    }
}
