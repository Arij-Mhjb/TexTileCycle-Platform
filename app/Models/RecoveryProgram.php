<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class RecoveryProgram extends Model {
    use HasFactory;
    protected $fillable = ['name','organization_name','type','description','accepted_materials','reward_points','is_active','starts_at','ends_at'];
    protected function casts(): array { return ['is_active'=>'boolean','starts_at'=>'date','ends_at'=>'date']; }
    public function collectionPoints(): HasMany { return $this->hasMany(CollectionPoint::class); }
}
