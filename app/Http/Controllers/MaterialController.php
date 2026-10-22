<?php
namespace App\Http\Controllers;
use App\Models\Material; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class MaterialController extends Controller {
 public function index(Request $r): View {$q=Material::withCount('products');if($r->filled('search'))$q->where('name','like','%'.$r->string('search').'%');return view('materials.index',['items'=>$q->orderBy('name')->paginate(10)->withQueryString()]);}
 public function create(): View{return view('materials.create',['item'=>new Material]);}
 public function store(Request $r): RedirectResponse{Material::create($this->validated($r));return to_route('materials.index')->with('success','Matière créée.');}
 public function edit(Material $material): View{return view('materials.edit',['item'=>$material]);}
 public function update(Request $r,Material $material): RedirectResponse{$material->update($this->validated($r,$material));return to_route('materials.index')->with('success','Matière mise à jour.');}
 public function destroy(Material $material): RedirectResponse{$material->delete();return back()->with('success','Matière supprimée.');}
 private function validated(Request $r,?Material $m=null):array{return $r->validate(['name'=>'required|string|max:120|unique:materials,name,'.($m?->id??'NULL'),'type'=>'required|in:plant,animal,synthetic,recycled,mineral,other','animal_origin'=>'boolean','recyclable'=>'boolean','description'=>'nullable|string']);}
}
