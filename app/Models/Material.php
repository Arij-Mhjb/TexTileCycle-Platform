<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Material extends Model {
    use HasFactory;
    protected $fillable = ['name','type','animal_origin','recyclable','description'];
    protected function casts(): array { return ['animal_origin'=>'boolean','recyclable'=>'boolean']; }
    public function products(): BelongsToMany { return $this->belongsToMany(Product::class)->withPivot(['percentage','origin_country','certified'])->withTimestamps(); }
}
