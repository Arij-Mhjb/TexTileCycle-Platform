<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoadmapStep extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(LabelRoadmap::class, 'label_roadmap_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(RoadmapSubmission::class)->orderByDesc('version');
    }
}
