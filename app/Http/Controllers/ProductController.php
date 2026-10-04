<?php
namespace App\Http\Controllers;
use App\Models\{Material,Product}; use Illuminate\Http\{RedirectResponse,Request}; use Illuminate\View\View;
class ProductController extends Controller {
 public function index(Request $r): View {$q=Product::withCount('materials'); if($r->filled('search')){$s=$r->string('search');$q->where(fn($x)=>$x->where('name','like',"%$s%")->orWhere('brand','like',"%$s%")->orWhere('sku','like',"%$s%"));} return view('products.index',['items'=>$q->latest()->paginate(10)->withQueryString()]);}
 public function create(): View {return view('products.create',['item'=>new Product,'materials'=>Material::orderBy('name')->get()]);}
 public function store(Request $r): RedirectResponse {$data=$this->validated($r);$ids=$data['material_ids']??[];unset($data['material_ids']);$p=Product::create($data);$p->materials()->sync($ids);return to_route('products.index')->with('success','Produit créé avec succès.');}
 public function edit(Product $product): View {return view('products.edit',['item'=>$product->load('materials'),'materials'=>Material::orderBy('name')->get()]);}
 public function update(Request $r,Product $product): RedirectResponse {$data=$this->validated($r,$product);$ids=$data['material_ids']??[];unset($data['material_ids']);$product->update($data);$product->materials()->sync($ids);return to_route('products.index')->with('success','Produit mis à jour.');}
 public function destroy(Product $product): RedirectResponse {$product->delete();return back()->with('success','Produit supprimé.');}
 private function validated(Request $r,?Product $p=null): array {return $r->validate(['name'=>'required|string|max:150','brand'=>'required|string|max:120','sku'=>'required|string|max:80|unique:products,sku,'.($p?->id??'NULL'),'category'=>'required|string|max:100','description'=>'nullable|string','status'=>'required|in:draft,published,archived','year'=>'nullable|integer|min:1900|max:'.(date('Y')+1),'repairable'=>'boolean','reusable'=>'boolean','recyclable'=>'boolean','environmental_score'=>'nullable|integer|min:0|max:100','circularity_score'=>'nullable|integer|min:0|max:100','animal_free_score'=>'nullable|integer|min:0|max:100','traceability_score'=>'nullable|integer|min:0|max:100','material_ids'=>'nullable|array','material_ids.*'=>'exists:materials,id']);}
}
