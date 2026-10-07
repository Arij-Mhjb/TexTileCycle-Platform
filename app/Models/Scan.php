<?php

namespace App\Models;

use App\Services\MediaStorageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'product_id', 'source_type', 'image_path', 'image_storage_disk',
        'image_cloudinary_public_id', 'image_secure_url', 'image_delivery_type', 'image_format', 'image_version',
        'raw_text', 'status',
        'confidence', 'result_summary', 'scanned_at', 'ai_status', 'extracted_data',
        'ai_explanation', 'consumer_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'consumer_confirmed_at' => 'datetime',
            'confidence' => 'decimal:2',
            'extracted_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Scan $scan) {
            $scan->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function detections(): HasMany
    {
        return $this->hasMany(Detection::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ScanAsset::class);
    }

    public function getImageUrlAttribute(): string
    {
        return app(MediaStorageService::class)->url(
            $this->image_path,
            $this->image_storage_disk,
            $this->image_cloudinary_public_id,
            $this->image_secure_url,
            $this->image_delivery_type,
            $this->image_format,
            $this->image_version,
        );
    }
}
