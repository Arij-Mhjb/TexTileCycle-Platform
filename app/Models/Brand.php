<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'logo_path', 'description', 'website', 'country', 'commitments', 'contact_email', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function activities(): HasMany { return $this->hasMany(ActivityLog::class)->latest(); }
    public function getRouteKeyName(): string { return 'slug'; }
}
