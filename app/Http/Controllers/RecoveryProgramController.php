<?php
namespace App\Http\Controllers;
use App\Models\RecoveryProgram; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class RecoveryProgramController extends Controller {
 public function index():View{return view('recovery-programs.index',['items'=>RecoveryProgram::withCount('collectionPoints')->latest()->paginate(10)]);}
 public function create():View{return view('recovery-programs.create',['item'=>new RecoveryProgram]);}
 public function store(Request $r):RedirectResponse{RecoveryProgram::create($this->validated($r));return to_route('recovery-programs.index')->with('success','Programme créé.');}
 public function edit(RecoveryProgram $recoveryProgram):View{return view('recovery-programs.edit',['item'=>$recoveryProgram]);}
 public function update(Request $r,RecoveryProgram $recoveryProgram):RedirectResponse{$recoveryProgram->update($this->validated($r));return to_route('recovery-programs.index')->with('success','Programme mis à jour.');}
 public function destroy(RecoveryProgram $recoveryProgram):RedirectResponse{$recoveryProgram->delete();return back()->with('success','Programme supprimé.');}
 private function validated(Request $r):array{return $r->validate(['name'=>'required|string|max:150','organization_name'=>'required|string|max:150','type'=>'required|in:repair,donation,take_back,recycling,reuse','description'=>'nullable|string','accepted_materials'=>'nullable|string','reward_points'=>'required|integer|min:0','is_active'=>'boolean','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at']);}
}
