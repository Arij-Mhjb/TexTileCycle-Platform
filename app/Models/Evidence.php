<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    use HasFactory;
    protected $table = 'evidences';
    protected $fillable = ['assessment_id','label_step_id','title','type','document_path','file_path','declaration','status','expires_at','notes','admin_comment','reviewed_by','reviewed_at','supersedes_id','version'];
    protected function casts(): array { return ['expires_at'=>'date','reviewed_at'=>'datetime']; }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function step(): BelongsTo { return $this->belongsTo(LabelStep::class, 'label_step_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function supersedes(): BelongsTo { return $this->belongsTo(Evidence::class, 'supersedes_id'); }
    public function comments() { return $this->hasMany(EvidenceComment::class); }
}
