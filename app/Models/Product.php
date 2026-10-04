<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model {
    use HasFactory;
    protected $fillable = ['name','brand','sku','category','description','status','year','repairable','reusable','recyclable','environmental_score','circularity_score','animal_free_score','traceability_score'];
    protected function casts(): array { return ['repairable'=>'boolean','reusable'=>'boolean','recyclable'=>'boolean']; }
    public function materials(): BelongsToMany { return $this->belongsToMany(Material::class)->withPivot(['percentage','origin_country','certified'])->withTimestamps(); }
    public function detections(): HasMany { return $this->hasMany(Detection::class); }
    public function returns(): HasMany { return $this->hasMany(ProductReturn::class); }
}
