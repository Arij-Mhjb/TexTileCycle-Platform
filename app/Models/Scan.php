<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Scan extends Model {
    use HasFactory;
    protected $fillable = ['user_id','source_type','image_path','raw_text','status','confidence','scanned_at'];
    protected function casts(): array { return ['scanned_at'=>'datetime','confidence'=>'decimal:2']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function detections(): HasMany { return $this->hasMany(Detection::class); }
}
