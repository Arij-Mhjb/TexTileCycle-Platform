<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refurbishment extends Model
{
    protected $fillable = ['repair_order_id', 'actions', 'grade', 'cost', 'technician', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'cost' => 'decimal:2'];
    }

    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }
}
