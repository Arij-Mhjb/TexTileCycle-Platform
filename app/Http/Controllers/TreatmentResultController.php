<?php
namespace App\Http\Controllers;
use App\Models\{ProductReturn,TreatmentResult}; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class TreatmentResultController extends Controller {
 public function index():View{return view('treatment-results.index',['items'=>TreatmentResult::with('productReturn.product')->latest()->paginate(10)]);}
 public function create():View{return view('treatment-results.create',['item'=>new TreatmentResult,'returns'=>ProductReturn::doesntHave('treatmentResult')->latest()->get()]);}
 public function store(Request $r):RedirectResponse{TreatmentResult::create($this->validated($r));return to_route('treatment-results.index')->with('success','Résultat créé.');}
 public function edit(TreatmentResult $treatmentResult):View{return view('treatment-results.edit',['item'=>$treatmentResult,'returns'=>ProductReturn::where('id',$treatmentResult->product_return_id)->orDoesntHave('treatmentResult')->latest()->get()]);}
 public function update(Request $r,TreatmentResult $treatmentResult):RedirectResponse{$treatmentResult->update($this->validated($r,$treatmentResult));return to_route('treatment-results.index')->with('success','Résultat mis à jour.');}
 public function destroy(TreatmentResult $treatmentResult):RedirectResponse{$treatmentResult->delete();return back()->with('success','Résultat supprimé.');}
 private function validated(Request $r,?TreatmentResult $x=null):array{$data=$r->validate(['product_return_id'=>'required|exists:product_returns,id|unique:treatment_results,product_return_id,'.($x?->id??'NULL'),'recycled_percent'=>'required|numeric|min:0|max:100','reused_percent'=>'required|numeric|min:0|max:100','waste_percent'=>'required|numeric|min:0|max:100','processed_at'=>'nullable|date','notes'=>'nullable|string']);if(round($data['recycled_percent']+$data['reused_percent']+$data['waste_percent'],2)!==100.0)abort(422,'La somme des résultats doit être égale à 100 %.');return $data;}
}
