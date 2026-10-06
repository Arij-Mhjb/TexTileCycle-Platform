<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'product_id', 'source_type', 'image_path', 'raw_text', 'status',
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

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function detections(): HasMany { return $this->hasMany(Detection::class); }
    public function returns(): HasMany { return $this->hasMany(ProductReturn::class); }
    public function assets(): HasMany { return $this->hasMany(ScanAsset::class); }
}
