<?php
namespace App\Services;
use App\Models\Product;
class ProductCompletenessService {
 public function analyse(Product $product): array {
  $product->loadMissing('materials');
  $checks=[
   'identity'=>filled($product->name)&&filled($product->brand)&&filled($product->category),
   'description_photo'=>filled($product->description)&&filled($product->image_path),
   'origin'=>filled($product->origin),
   'composition'=>$product->materials->isNotEmpty() && abs((float)$product->materials->sum(fn($m)=>(float)$m->pivot->percentage)-100)<0.01,
   'identifier'=>filled($product->barcode)||filled($product->qr_code),
   'recyclability'=>$product->recyclability_score!==null,
   'end_of_life'=>filled($product->recycling_instructions),
  ];
  $weights=['identity'=>10,'description_photo'=>15,'origin'=>10,'composition'=>25,'identifier'=>10,'recyclability'=>15,'end_of_life'=>15];
  $score=collect($checks)->sum(fn($ok,$key)=>$ok?$weights[$key]:0);
  return ['score'=>$score,'ready'=>$score===100,'checks'=>$checks,'missing'=>collect($checks)->filter(fn($ok)=>!$ok)->keys()->values()->all()];
 }
 public function refresh(Product $product): array { $result=$this->analyse($product); $product->updateQuietly(['completion_score'=>$result['score']]); return $result; }
}
