<?php
namespace App\Http\Controllers;
use App\Models\Assessment;use App\Models\Evidence;use App\Models\Label;use App\Services\ActivityService;use App\Services\AssessmentProgressService;
use Illuminate\Http\RedirectResponse;use Illuminate\Http\Request;use Illuminate\View\View;
class BrandLabelController extends Controller {
 public function index(Request $request): View { return view('brand.labels.index',['labels'=>Label::where('is_active',true)->withCount('steps')->get(),'assessments'=>$request->user()->assessments()->with('label')->get()->keyBy('label_id')]); }
 public function show(Request $request,Label $label): View {
  $label->load('steps');$assessment=$request->user()->assessments()->where('label_id',$label->id)->with('evidences.comments.user')->first();
  return view('brand.labels.show',compact('label','assessment'));
 }
 public function apply(Request $request,Label $label): RedirectResponse {
  Assessment::firstOrCreate(['user_id'=>$request->user()->id,'label_id'=>$label->id],['organization_name'=>$request->user()->brand->name,'framework'=>$label->name,'status'=>'draft','assessment_date'=>today()]);
  app(ActivityService::class)->log($request->user()->id,$request->user()->brand->id,'assessment.created',$label);
  return to_route('brand.labels.show',$label)->with('success','Dossier créé.');
 }
 public function evidence(Request $request,Assessment $assessment): RedirectResponse {
  abort_unless($assessment->user_id===$request->user()->id,403);
  $data=$request->validate(['label_step_id'=>'required|exists:label_steps,id','type'=>'required|in:file,declaration','file'=>'nullable|required_if:type,file|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120','declaration'=>'nullable|required_if:type,declaration|string|max:5000']);
  abort_unless($assessment->label->steps()->whereKey($data['label_step_id'])->exists(),422);
  $previous=$assessment->evidences()->where('label_step_id',$data['label_step_id'])->latest('version')->first();
  $path=$request->hasFile('file')?$request->file('file')->store('evidences','local'):null;
  $evidence=Evidence::create(['assessment_id'=>$assessment->id,'label_step_id'=>$data['label_step_id'],'title'=>$assessment->label->steps()->find($data['label_step_id'])->title,'type'=>$data['type'],'file_path'=>$path,'document_path'=>$path,'declaration'=>$data['declaration']??null,'status'=>'pending','supersedes_id'=>$previous?->id,'version'=>($previous?->version??0)+1]);
  app(AssessmentProgressService::class)->refresh($assessment);
  app(ActivityService::class)->log($request->user()->id,$request->user()->brand->id,'evidence.submitted',$evidence,['version'=>$evidence->version]);
  return back()->with('success','Nouvelle version de la preuve envoyée.');
 }
 public function submit(Request $request,Assessment $assessment): RedirectResponse {
  abort_unless($assessment->user_id===$request->user()->id,403);$assessment->update(['submitted_at'=>now(),'status'=>'in_review']);app(AssessmentProgressService::class)->refresh($assessment);
  app(ActivityService::class)->log($request->user()->id,$request->user()->brand->id,'assessment.submitted',$assessment);return back()->with('success','Dossier soumis.');
 }
}
