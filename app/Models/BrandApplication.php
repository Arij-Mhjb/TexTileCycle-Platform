<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandApplication extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'recommendations' => 'array', 'approved_label_snapshot' => 'array',
            'analysed_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
