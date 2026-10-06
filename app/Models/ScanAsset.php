<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ScanAsset extends Model
{
    protected $fillable = ['scan_id', 'type', 'path', 'extracted_text', 'confidence'];

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
        return Storage::disk('public')->url($this->path);
    }
}
