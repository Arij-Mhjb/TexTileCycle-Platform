<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TreatmentResult extends Model {
    use HasFactory;
    protected $fillable = ['product_return_id','recycled_percent','reused_percent','waste_percent','processed_at','notes'];
    protected function casts(): array { return ['processed_at'=>'datetime','recycled_percent'=>'decimal:2','reused_percent'=>'decimal:2','waste_percent'=>'decimal:2']; }
    public function productReturn(): BelongsTo { return $this->belongsTo(ProductReturn::class); }
}
