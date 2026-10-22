<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RepairWorkshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_partner_id',
        'name',
        'slug',
        'city',
        'address',
        'specialties',
        'description',
        'contact_name',
        'phone',
        'email',
        'capacity_per_week',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity_per_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $workshop) {
            $workshop->slug ??= Str::slug($workshop->name);
        });

        static::updating(function (self $workshop) {
            if ($workshop->isDirty('name') && empty($workshop->slug)) {
                $workshop->slug = Str::slug($workshop->name);
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(RepairPartner::class, 'repair_partner_id');
    }
}
