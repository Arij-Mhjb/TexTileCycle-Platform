<?php
namespace App\Http\Controllers;
use App\Models\Evidence;use App\Services\ActivityService;use App\Services\AssessmentProgressService;
use Illuminate\Http\RedirectResponse;use Illuminate\Http\Request;
class AdminEvidenceReviewController extends Controller {
 public function update(Request $request,Evidence $evidence): RedirectResponse {
  $data=$request->validate(['status'=>'required|in:approved,rejected','admin_comment'=>'required_if:status,rejected|nullable|string|max:3000']);
  $evidence->update($data+['reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);
  if(filled($data['admin_comment']??null))$evidence->comments()->create(['user_id'=>$request->user()->id,'body'=>$data['admin_comment']]);
  app(AssessmentProgressService::class)->refresh($evidence->assessment);
  $brand=$evidence->assessment->user->brand;
  app(ActivityService::class)->log($request->user()->id,$brand?->id,'evidence.'.$data['status'],$evidence,['comment'=>$data['admin_comment']??null]);
  if($evidence->assessment->user_id)app(ActivityService::class)->notify($evidence->assessment->user_id,'evidence_'.$data['status'],$data['status']==='approved'?'Preuve approuvée':'Preuve rejetée',"{$evidence->title} : ".($data['status']==='approved'?'preuve vérifiée.':'correction nécessaire.'),$evidence->assessment->label?route('brand.labels.show',$evidence->assessment->label):route('brand.dashboard'));
  return back()->with('success','Décision enregistrée.');
 }
}
