<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterFootprint extends Model
{
    protected $fillable = ['product_impact_report_id', 'stage', 'liters'];

    protected function casts(): array
    {
        return ['liters' => 'decimal:2'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ProductImpactReport::class, 'product_impact_report_id');
    }
}
