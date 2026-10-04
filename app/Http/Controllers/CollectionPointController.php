<?php
namespace App\Http\Controllers;
use App\Models\{CollectionPoint,RecoveryProgram}; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class CollectionPointController extends Controller {
 public function index():View{return view('collection-points.index',['items'=>CollectionPoint::with('recoveryProgram')->latest()->paginate(10)]);}
 public function create():View{return view('collection-points.create',['item'=>new CollectionPoint,'programs'=>RecoveryProgram::orderBy('name')->get()]);}
 public function store(Request $r):RedirectResponse{CollectionPoint::create($this->validated($r));return to_route('collection-points.index')->with('success','Point de collecte créé.');}
 public function edit(CollectionPoint $collectionPoint):View{return view('collection-points.edit',['item'=>$collectionPoint,'programs'=>RecoveryProgram::orderBy('name')->get()]);}
 public function update(Request $r,CollectionPoint $collectionPoint):RedirectResponse{$collectionPoint->update($this->validated($r));return to_route('collection-points.index')->with('success','Point de collecte mis à jour.');}
 public function destroy(CollectionPoint $collectionPoint):RedirectResponse{$collectionPoint->delete();return back()->with('success','Point de collecte supprimé.');}
 private function validated(Request $r):array{return $r->validate(['recovery_program_id'=>'required|exists:recovery_programs,id','name'=>'required|string|max:150','address'=>'required|string|max:255','city'=>'required|string|max:100','latitude'=>'nullable|numeric|between:-90,90','longitude'=>'nullable|numeric|between:-180,180','opening_hours'=>'nullable|string|max:150','is_active'=>'boolean']);}
}
