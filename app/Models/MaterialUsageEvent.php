<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsageEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['files' => 'array', 'measurements' => 'array'];
    }

    public function usage(): BelongsTo
    {
        return $this->belongsTo(MaterialUsage::class, 'material_usage_id');
    }
}
