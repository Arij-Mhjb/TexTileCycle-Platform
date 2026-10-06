<?php
namespace App\Services;
use App\Models\Assessment;
class AssessmentProgressService {
 public function refresh(Assessment $assessment): Assessment {
  $assessment->loadMissing('label.steps','evidences');
  $steps=$assessment->label?->steps??collect();$total=(int)$steps->sum('weight');
  $latest=$assessment->evidences->sortByDesc('version')->groupBy('label_step_id')->map->first();
  $approvedWeight=(int)$steps->filter(fn($step)=>$latest->get($step->id)?->status==='approved')->sum('weight');
  $score=$total>0?(int)round(($approvedWeight/$total)*100):0;
  $assessment->update(['score'=>$score,'readiness_score'=>$score,'status'=>$score===100?'verified_evidence':($assessment->submitted_at?'in_review':'draft')]);
  return $assessment->fresh();
 }
}
