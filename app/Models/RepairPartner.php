<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RepairPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'city',
        'address',
        'description',
        'contact_name',
        'email',
        'phone',
        'website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $partner) {
            $partner->slug ??= Str::slug($partner->name);
        });

        static::updating(function (self $partner) {
            if ($partner->isDirty('name') && empty($partner->slug)) {
                $partner->slug = Str::slug($partner->name);
            }
        });
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(RepairWorkshop::class);
    }
}
