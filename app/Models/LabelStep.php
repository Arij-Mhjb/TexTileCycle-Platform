<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelStep extends Model
{
    protected $fillable = ['label_id', 'title', 'description', 'position'];
    public function label(): BelongsTo { return $this->belongsTo(Label::class); }
    public function evidences(): HasMany { return $this->hasMany(Evidence::class); }
}
