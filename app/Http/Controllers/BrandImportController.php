<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Services\ActivityService;
use App\Services\ProductCompletenessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
class BrandImportController extends Controller {
 public function create(): View { return view('brand.products.import'); }
 public function store(Request $request): RedirectResponse {
  $request->validate(['file'=>'required|file|mimes:csv,txt|max:5120']);
  $handle=fopen($request->file('file')->getRealPath(),'r'); $headers=array_map(fn($v)=>trim(mb_strtolower($v)),fgetcsv($handle)?:[]);
  $required=['name','sku','category']; $missing=array_diff($required,$headers);
  if($missing){fclose($handle);return back()->withErrors(['file'=>'Colonnes obligatoires manquantes : '.implode(', ',$missing)]);}
  $created=0;$errors=[];
  while(($row=fgetcsv($handle))!==false){
   if(count($row)!==count($headers)){$errors[]='Ligne '.($created+2).' invalide.';continue;}
   $data=array_combine($headers,$row); if(blank($data['name'])||blank($data['sku'])||blank($data['category']))continue;
   if(Product::where('sku',$data['sku'])->exists()){$errors[]="SKU {$data['sku']} déjà présent.";continue;}
   $product=Product::create([
    'brand_id'=>$request->user()->brand->id,'brand'=>$request->user()->brand->name,'name'=>$data['name'],
    'slug'=>Str::slug($data['name']).'-'.Str::lower(Str::random(5)),'sku'=>$data['sku'],'category'=>$data['category'],
    'description'=>$data['description']??null,'origin'=>$data['origin']??null,'barcode'=>$data['barcode']??null,
    'recyclability_score'=>filled($data['recyclability_score']??null)?(int)$data['recyclability_score']:null,
    'recycling_instructions'=>$data['recycling_instructions']??null,'status'=>'draft','is_active'=>false,
   ]);
   app(ProductCompletenessService::class)->refresh($product);$created++;
  }
  fclose($handle);
  app(ActivityService::class)->log($request->user()->id,$request->user()->brand->id,'products.imported',null,['created'=>$created,'errors'=>$errors]);
  return to_route('brand.products.index')->with('success',"$created produit(s) importé(s).".($errors?' '.count($errors).' ligne(s) ignorée(s).':''));
 }
}
