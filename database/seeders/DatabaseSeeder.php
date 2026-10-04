<?php
namespace Database\Seeders;
use App\Models\{Assessment,CollectionPoint,Detection,Evidence,Material,Product,ProductReturn,RecoveryProgram,Scan,TreatmentResult,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  $user=User::updateOrCreate(['email'=>'demo@textilecycle.test'],['name'=>'Walaeddine Demo','role'=>'admin','email_verified_at'=>now(),'password'=>Hash::make('password')]);
  $cotton=Material::create(['name'=>'Coton recyclé','type'=>'recycled','recyclable'=>true,'description'=>'Fibre de coton issue de déchets pré-consommation ou post-consommation.']);
  $wool=Material::create(['name'=>'Laine','type'=>'animal','animal_origin'=>true,'recyclable'=>true,'description'=>'Fibre animale nécessitant une traçabilité renforcée.']);
  $poly=Material::create(['name'=>'Polyester recyclé','type'=>'recycled','recyclable'=>true]);
  $linen=Material::create(['name'=>'Lin','type'=>'plant','recyclable'=>true]);
  $jacket=Product::create(['name'=>'Winter Jacket 02','brand'=>'EcoFashion','sku'=>'EF-WJ-002','category'=>'Veste','description'=>'Veste chaude avec passeport textile complet.','status'=>'published','year'=>2026,'repairable'=>true,'reusable'=>true,'recyclable'=>true,'environmental_score'=>79,'circularity_score'=>88,'animal_free_score'=>60,'traceability_score'=>91]);
  $jacket->materials()->attach([$poly->id=>['percentage'=>60,'certified'=>true],$wool->id=>['percentage'=>40,'certified'=>false]]);
  $shirt=Product::create(['name'=>'ReLoop Shirt','brand'=>'Circular Studio','sku'=>'CS-RS-104','category'=>'Chemise','status'=>'published','year'=>2026,'repairable'=>true,'reusable'=>true,'recyclable'=>true,'environmental_score'=>86,'circularity_score'=>92,'animal_free_score'=>100,'traceability_score'=>84]);
  $shirt->materials()->attach([$cotton->id=>['percentage'=>70,'certified'=>true],$linen->id=>['percentage'=>30,'certified'=>true]]);
  $program=RecoveryProgram::create(['name'=>'Reprise textile toutes marques','organization_name'=>'EcoFashion Tunis','type'=>'take_back','description'=>'Collecte des vêtements pour tri, réemploi et recyclage.','accepted_materials'=>'Coton, laine, polyester, lin','reward_points'=>150,'is_active'=>true,'starts_at'=>now()->startOfYear()]);
  $point=CollectionPoint::create(['recovery_program_id'=>$program->id,'name'=>'EcoFashion Lac 2','address'=>'Rue de la Bourse, Les Berges du Lac 2','city'=>'Tunis','latitude'=>36.8487,'longitude'=>10.2685,'opening_hours'=>'Lun–Sam · 09:00–19:00','is_active'=>true]);
  $scan=Scan::create(['user_id'=>$user->id,'source_type'=>'label','raw_text'=>'EcoFashion Winter Jacket EF-WJ-002 60% recycled polyester 40% wool','status'=>'completed','confidence'=>94.5,'scanned_at'=>now()]);
  Detection::create(['scan_id'=>$scan->id,'product_id'=>$jacket->id,'detected_brand'=>'EcoFashion','detected_name'=>'Winter Jacket 02','detected_category'=>'Veste','detected_composition'=>'60 % polyester recyclé, 40 % laine','confidence'=>94.5,'is_confirmed'=>true]);
  $return=ProductReturn::create(['user_id'=>$user->id,'product_id'=>$jacket->id,'collection_point_id'=>$point->id,'reference'=>'RET-2026-0001','status'=>'processed','weight_kg'=>1.2,'returned_at'=>now()->subDays(3),'notes'=>'Produit en bon état, réemploi prioritaire.']);
  TreatmentResult::create(['product_return_id'=>$return->id,'recycled_percent'=>60,'reused_percent'=>25,'waste_percent'=>15,'processed_at'=>now()->subDay()]);
  $assessment=Assessment::create(['organization_name'=>'EcoFashion','framework'=>'ISO 14001','status'=>'in_progress','readiness_score'=>81,'assessment_date'=>now()->toDateString(),'next_review_date'=>now()->addMonths(6)->toDateString(),'notes'=>'Objectif eau annuel à compléter.']);
  Evidence::create(['assessment_id'=>$assessment->id,'title'=>'Rapport annuel de collecte 2026','type'=>'report','status'=>'verified','expires_at'=>now()->addYear()->toDateString()]);
 }
}
