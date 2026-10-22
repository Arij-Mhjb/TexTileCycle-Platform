<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardItem extends Model
{
    protected $fillable = ['name', 'description', 'points_cost', 'partner_name', 'stock', 'available_until', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'available_until' => 'datetime'];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && ($this->stock === null || $this->stock > 0)
            && ($this->available_until === null || $this->available_until->isFuture());
    }
}
