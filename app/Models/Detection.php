<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Detection extends Model {
    use HasFactory;
    protected $fillable = ['scan_id','product_id','detected_brand','detected_name','detected_category','detected_composition','confidence','is_confirmed'];
    protected function casts(): array { return ['confidence'=>'decimal:2','is_confirmed'=>'boolean']; }
    public function scan(): BelongsTo { return $this->belongsTo(Scan::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
