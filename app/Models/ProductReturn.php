<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class ProductReturn extends Model {
    use HasFactory;
    protected $fillable = ['user_id','product_id','collection_point_id','reference','status','weight_kg','returned_at','notes'];
    protected function casts(): array { return ['returned_at'=>'datetime','weight_kg'=>'decimal:2']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function collectionPoint(): BelongsTo { return $this->belongsTo(CollectionPoint::class); }
    public function treatmentResult(): HasOne { return $this->hasOne(TreatmentResult::class); }
}
