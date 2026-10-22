<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapSubmission extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scope_version' => 'integer', 'version' => 'integer', 'product_results' => 'array', 'files' => 'array', 'scope_snapshot' => 'array', 'step_snapshot' => 'array', 'material_record' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(RoadmapStep::class, 'roadmap_step_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
