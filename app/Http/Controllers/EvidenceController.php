<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Evidence}; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class EvidenceController extends Controller {
 public function index():View{return view('evidences.index',['items'=>Evidence::with('assessment')->latest()->paginate(10)]);}
 public function create():View{return view('evidences.create',['item'=>new Evidence,'assessments'=>Assessment::latest()->get()]);}
 public function store(Request $r):RedirectResponse{Evidence::create($this->validated($r));return to_route('evidences.index')->with('success','Preuve créée.');}
 public function edit(Evidence $evidence):View{return view('evidences.edit',['item'=>$evidence,'assessments'=>Assessment::latest()->get()]);}
 public function update(Request $r,Evidence $evidence):RedirectResponse{$evidence->update($this->validated($r));return to_route('evidences.index')->with('success','Preuve mise à jour.');}
 public function destroy(Evidence $evidence):RedirectResponse{$evidence->delete();return back()->with('success','Preuve supprimée.');}
 private function validated(Request $r):array{return $r->validate(['assessment_id'=>'required|exists:assessments,id','title'=>'required|string|max:180','type'=>'required|in:certificate,report,invoice,photo,policy,other','document_path'=>'nullable|string|max:255','status'=>'required|in:pending,verified,rejected,expired','expires_at'=>'nullable|date','notes'=>'nullable|string']);}
}
