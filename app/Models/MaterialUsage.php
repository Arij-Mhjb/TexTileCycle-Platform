<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialUsage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scope_version' => 'integer', 'input_kg' => 'decimal:2', 'recycled_output_kg' => 'decimal:2', 'incorporated_kg' => 'decimal:2', 'product_quantities' => 'array'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MaterialMatch::class, 'material_match_id');
    }

    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(LabelRoadmap::class, 'label_roadmap_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MaterialUsageEvent::class)->orderBy('id');
    }

    public function statusLabel(): string
    {
        return ['stored' => 'Matière reçue — en stock', 'sent' => 'Transmise au partenaire', 'processed' => 'Transformation documentée', 'incorporated' => 'Incorporation documentée'][$this->status] ?? $this->status;
    }
}
