<?php
namespace App\Http\Controllers;
use App\Models\{Detection,Product,Scan}; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class DetectionController extends Controller {
 public function index():View{return view('detections.index',['items'=>Detection::with(['scan','product'])->latest()->paginate(10)]);}
 public function create():View{return view('detections.create',['item'=>new Detection,'scans'=>Scan::latest()->get(),'products'=>Product::orderBy('name')->get()]);}
 public function store(Request $r):RedirectResponse{Detection::create($this->validated($r));return to_route('detections.index')->with('success','Détection créée.');}
 public function edit(Detection $detection):View{return view('detections.edit',['item'=>$detection,'scans'=>Scan::latest()->get(),'products'=>Product::orderBy('name')->get()]);}
 public function update(Request $r,Detection $detection):RedirectResponse{$detection->update($this->validated($r));return to_route('detections.index')->with('success','Détection mise à jour.');}
 public function destroy(Detection $detection):RedirectResponse{$detection->delete();return back()->with('success','Détection supprimée.');}
 private function validated(Request $r):array{return $r->validate(['scan_id'=>'required|exists:scans,id','product_id'=>'nullable|exists:products,id','detected_brand'=>'nullable|string|max:120','detected_name'=>'required|string|max:150','detected_category'=>'nullable|string|max:100','detected_composition'=>'nullable|string','confidence'=>'nullable|numeric|min:0|max:100','is_confirmed'=>'boolean']);}
}
