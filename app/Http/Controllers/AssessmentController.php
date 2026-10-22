<?php
namespace App\Http\Controllers;
use App\Models\Assessment; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class AssessmentController extends Controller {
 public function index():View{return view('assessments.index',['items'=>Assessment::withCount('evidences')->latest()->paginate(10)]);}
 public function create():View{return view('assessments.create',['item'=>new Assessment]);}
 public function store(Request $r):RedirectResponse{Assessment::create($this->validated($r));return to_route('assessments.index')->with('success','Évaluation créée.');}
 public function edit(Assessment $assessment):View{return view('assessments.edit',['item'=>$assessment]);}
 public function update(Request $r,Assessment $assessment):RedirectResponse{$assessment->update($this->validated($r));return to_route('assessments.index')->with('success','Évaluation mise à jour.');}
 public function destroy(Assessment $assessment):RedirectResponse{$assessment->delete();return back()->with('success','Évaluation supprimée.');}
 private function validated(Request $r):array{return $r->validate(['organization_name'=>'required|string|max:150','framework'=>'required|in:ISO 14001,ISO 50001,GRS,OEKO-TEX,Internal','status'=>'required|in:draft,in_progress,ready,needs_action','readiness_score'=>'nullable|integer|min:0|max:100','assessment_date'=>'required|date','next_review_date'=>'nullable|date|after_or_equal:assessment_date','notes'=>'nullable|string']);}
}
