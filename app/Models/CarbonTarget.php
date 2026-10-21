<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Objectif carbone d'une marque : budget total de CO₂e pour une collection et/ou plafond par unité produite. */
class CarbonTarget extends Model
{
    protected $fillable = ['brand_id', 'created_by', 'name', 'season', 'budget_kg', 'max_unit_kg', 'planned_units', 'deadline'];

    protected function casts(): array
    {
        return ['planned_units' => 'array', 'deadline' => 'date', 'budget_kg' => 'decimal:2', 'max_unit_kg' => 'decimal:3'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
