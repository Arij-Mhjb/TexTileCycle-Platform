<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id','name','slug','brand','sku','barcode','qr_code','category','description','origin','image_path',
        'status','year','repairable','reusable','recyclable','recyclability_score','recycling_instructions','is_active',
        'environmental_score','circularity_score','animal_free_score','traceability_score',
        'completion_score','current_version','review_comment','submitted_at','verified_at','published_at','archived_at',
    ];

    protected function casts(): array
    {
        return ['repairable'=>'boolean','reusable'=>'boolean','recyclable'=>'boolean','is_active'=>'boolean','submitted_at'=>'datetime','verified_at'=>'datetime','published_at'=>'datetime','archived_at'=>'datetime'];
    }

    public function brandProfile(): BelongsTo { return $this->belongsTo(Brand::class, 'brand_id'); }
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)
            ->withPivot(['percentage','origin_country','certified','component','supplier','sourcing_type','evidence_path'])->withTimestamps();
    }
    public function detections(): HasMany { return $this->hasMany(Detection::class); }
    public function returns(): HasMany { return $this->hasMany(ProductReturn::class); }
    public function scans(): HasMany { return $this->hasMany(Scan::class); }
    public function versions(): HasMany { return $this->hasMany(ProductVersion::class); }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) return Storage::disk('public')->url($this->image_path);
        return asset('images/story/clothes-sorting.jpg');
    }
}
