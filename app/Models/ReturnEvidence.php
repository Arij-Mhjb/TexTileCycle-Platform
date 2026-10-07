<?php

namespace App\Models;

use App\Services\MediaStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnEvidence extends Model
{
    protected $fillable = [
        'product_return_id', 'user_id', 'type', 'path', 'storage_disk',
        'cloudinary_public_id', 'secure_url', 'delivery_type', 'format', 'version', 'notes',
    ];

    public function productReturn(): BelongsTo { return $this->belongsTo(ProductReturn::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function getUrlAttribute(): string
    {
        return app(MediaStorageService::class)->url(
            $this->path, $this->storage_disk, $this->cloudinary_public_id, $this->secure_url,
            $this->delivery_type, $this->format, $this->version,
        );
    }
}
