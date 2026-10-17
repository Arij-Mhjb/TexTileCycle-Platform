<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImpactCategory extends Model
{
    protected $fillable = ['code', 'name', 'unit', 'weight', 'description'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ImpactMetric::class);
    }
}
