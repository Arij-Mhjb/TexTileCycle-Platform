<?php

namespace App\Models;

use App\Services\MediaStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanAsset extends Model
{
    protected $fillable = [
        'scan_id', 'type', 'path', 'storage_disk', 'cloudinary_public_id', 'secure_url',
        'delivery_type', 'format', 'version', 'extracted_text', 'confidence',
    ];

    protected function casts(): array
    {
        return ['confidence' => 'decimal:2'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function getUrlAttribute(): string
    {
        return app(MediaStorageService::class)->url(
            $this->path,
            $this->storage_disk,
            $this->cloudinary_public_id,
            $this->secure_url,
            $this->delivery_type,
            $this->format,
            $this->version,
        );
    }

    public function getIsAvailableAttribute(): bool
    {
        // A durable Cloudinary identifier is enough for display. A real existence
        // check is performed immediately before an OCR reanalysis.
        if ($this->storage_disk === 'cloudinary') {
            return filled($this->cloudinary_public_id);
        }

        return app(MediaStorageService::class)->exists($this->path, $this->storage_disk);
    }
}
