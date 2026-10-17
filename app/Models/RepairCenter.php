<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairCenter extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'city', 'address', 'specialties', 'contact_email', 'phone', 'capacity_per_week', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function repairOrders(): HasMany
    {
        return $this->hasMany(RepairOrder::class);
    }
}
