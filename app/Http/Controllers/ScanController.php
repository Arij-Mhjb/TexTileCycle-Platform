<?php
namespace App\Http\Controllers;
use App\Models\Scan; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class ScanController extends Controller {
 public function index():View{return view('scans.index',['items'=>Scan::withCount('detections')->latest()->paginate(10)]);}
 public function create():View{return view('scans.create',['item'=>new Scan]);}
 public function store(Request $r):RedirectResponse{$data=$this->validated($r);$data['user_id']=$r->user()->id;$data['scanned_at']=$data['scanned_at']??now();Scan::create($data);return to_route('scans.index')->with('success','Scan enregistré.');}
 public function edit(Scan $scan):View{return view('scans.edit',['item'=>$scan]);}
 public function update(Request $r,Scan $scan):RedirectResponse{$scan->update($this->validated($r));return to_route('scans.index')->with('success','Scan mis à jour.');}
 public function destroy(Scan $scan):RedirectResponse{$scan->delete();return back()->with('success','Scan supprimé.');}
 private function validated(Request $r):array{return $r->validate(['source_type'=>'required|in:ticket,label,barcode,qr','image_path'=>'nullable|string|max:255','raw_text'=>'nullable|string','status'=>'required|in:pending,processing,completed,failed','confidence'=>'nullable|numeric|min:0|max:100','scanned_at'=>'nullable|date']);}
}
