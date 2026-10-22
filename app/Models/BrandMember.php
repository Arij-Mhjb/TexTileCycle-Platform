<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BrandMember extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['invited_at'=>'datetime','joined_at'=>'datetime'];}
 public function brand():BelongsTo{return $this->belongsTo(Brand::class);}
 public function user():BelongsTo{return $this->belongsTo(User::class);}
 public function steps():HasMany{return $this->hasMany(RoadmapStep::class);}
}
