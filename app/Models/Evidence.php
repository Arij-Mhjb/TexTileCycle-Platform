<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Evidence extends Model {
    use HasFactory;
    protected $table = 'evidences';
    protected $fillable = ['assessment_id','title','type','document_path','status','expires_at','notes'];
    protected function casts(): array { return ['expires_at'=>'date']; }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
}
