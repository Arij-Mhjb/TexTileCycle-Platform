<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialMatch extends Model
{
    protected $fillable = [
        'material_request_id', 'product_return_id', 'match_score', 'status', 'transfer_token',
        'accepted_at', 'due_at', 'transferred_at', 'brand_received_at', 'brand_verified_at',
        'brand_review_note', 'ai_verdict', 'ai_confidence', 'ai_reason', 'ai_details',
        'ai_source', 'ai_analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'decimal:2',
            'ai_confidence' => 'decimal:2',
            'ai_details' => 'array',
            'ai_analyzed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'due_at' => 'datetime',
            'transferred_at' => 'datetime',
            'brand_received_at' => 'datetime',
            'brand_verified_at' => 'datetime',
        ];
    }

    public function usage(): HasOne
    {
        return $this->hasOne(MaterialUsage::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }
}
