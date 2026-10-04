<?php
namespace App\Http\Controllers;
use App\Models\{CollectionPoint,Product,ProductReturn}; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class ProductReturnController extends Controller {
 public function index():View{return view('product-returns.index',['items'=>ProductReturn::with(['product','collectionPoint'])->latest()->paginate(10)]);}
 public function create():View{return view('product-returns.create',['item'=>new ProductReturn,'products'=>Product::orderBy('name')->get(),'points'=>CollectionPoint::orderBy('name')->get()]);}
 public function store(Request $r):RedirectResponse{$data=$this->validated($r);$data['user_id']=$r->user()->id;ProductReturn::create($data);return to_route('product-returns.index')->with('success','Retour enregistré.');}
 public function edit(ProductReturn $productReturn):View{return view('product-returns.edit',['item'=>$productReturn,'products'=>Product::orderBy('name')->get(),'points'=>CollectionPoint::orderBy('name')->get()]);}
 public function update(Request $r,ProductReturn $productReturn):RedirectResponse{$productReturn->update($this->validated($r,$productReturn));return to_route('product-returns.index')->with('success','Retour mis à jour.');}
 public function destroy(ProductReturn $productReturn):RedirectResponse{$productReturn->delete();return back()->with('success','Retour supprimé.');}
 private function validated(Request $r,?ProductReturn $x=null):array{return $r->validate(['product_id'=>'required|exists:products,id','collection_point_id'=>'required|exists:collection_points,id','reference'=>'required|string|max:80|unique:product_returns,reference,'.($x?->id??'NULL'),'status'=>'required|in:declared,received,sorting,processed,rejected','weight_kg'=>'nullable|numeric|min:0|max:999999','returned_at'=>'nullable|date','notes'=>'nullable|string']);}
}
