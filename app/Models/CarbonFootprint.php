<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarbonFootprint extends Model
{
    public const STAGES = ['materials' => 'Matières', 'manufacturing' => 'Fabrication', 'transport' => 'Transport', 'end_of_life' => 'Fin de vie'];

    protected $fillable = ['product_impact_report_id', 'stage', 'kg_co2e'];

    protected function casts(): array
    {
        return ['kg_co2e' => 'decimal:3'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ProductImpactReport::class, 'product_impact_report_id');
    }
}
