<?php
namespace App\Http\Controllers;
use App\Models\RoadmapStep;
use App\Services\LabelRoadmapService;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class BrandMemberTaskController extends Controller {
 private function own(Request $r,RoadmapStep $step):void{abort_unless($step->brand_member_id===$r->user()->brandMembership?->id,403);}
 public function index(Request $r):View
 {
     $member = $r->user()->brandMembership;
     $steps = $member->steps()
         ->with(['roadmap.label', 'roadmap.products'])
         ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
         ->orderBy('due_date')
         ->get();

     return view('member.tasks.index', [
         'member' => $member,
         'steps' => $steps,
         'stats' => [
             'total' => $steps->count(),
             'todo' => $steps->whereIn('status', ['todo', 'changes_requested'])->count(),
             'in_progress' => $steps->where('status', 'in_progress')->count(),
             'submitted' => $steps->where('status', 'submitted')->count(),
             'approved' => $steps->where('status', 'approved')->count(),
             'overdue' => $steps->filter(fn ($step) => $step->status !== 'approved' && $step->due_date?->isPast())->count(),
         ],
     ]);
 }
 public function show(Request $r,RoadmapStep $step):View{$this->own($r,$step);$step->load('roadmap.products','roadmap.label','submissions.reviewer');return view('member.tasks.show',compact('step'));}
 public function submit(Request $r,RoadmapStep $step,LabelRoadmapService $service,MediaStorageService $media){$this->own($r,$step);$roadmap=$step->roadmap;$rules=['scope_version'=>['required','integer',Rule::in([$roadmap->scope_version])],'actions'=>'required|string|min:10|max:5000','results'=>'required|string|min:10|max:5000','product_results'=>'required|array','proofs'=>'required|array|min:1|max:4','proofs.*'=>'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,csv,txt|max:8192'];foreach($roadmap->products()->pluck('products.id') as $id)$rules['product_results.'.$id]='required|string|min:5|max:2000';$data=$r->validate($rules);$files=[];try{foreach($r->file('proofs') as $file)$files[]=$media->storeRoadmapProof($file,$roadmap->id);$service->submit($r->user(),$step,$data,$files);}catch(\Throwable $e){foreach($files as $file)try{$media->deleteRoadmapProof($file);}catch(\Throwable $ignored){}throw $e;}return back()->with('success','Travail soumis au nom de '.$roadmap->brand->name.'.');}
}
