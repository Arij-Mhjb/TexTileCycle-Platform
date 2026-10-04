<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Assessment extends Model {
    use HasFactory;
    protected $fillable = ['organization_name','framework','status','readiness_score','assessment_date','next_review_date','notes'];
    protected function casts(): array { return ['assessment_date'=>'date','next_review_date'=>'date']; }
    public function evidences(): HasMany { return $this->hasMany(Evidence::class); }
}
