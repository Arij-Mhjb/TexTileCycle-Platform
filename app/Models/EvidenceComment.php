<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EvidenceComment extends Model {
 protected $fillable=['evidence_id','user_id','body'];
 public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
