<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Brand;
use App\Models\Evidence;
use App\Models\Label;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Services\AssessmentProgressService;
use App\Services\ProductCompletenessService;
use App\Services\ProductWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalBrandWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function brandUser(string $name='Marque A'): User
    {
        $user=User::factory()->create(['role'=>'brand']);
        Brand::create(['user_id'=>$user->id,'name'=>$name,'slug'=>str($name)->slug()]);
        return $user->fresh();
    }

    public function test_composition_must_total_exactly_one_hundred_percent(): void
    {
        $user=$this->brandUser();
        $material=Material::create(['name'=>'Coton test','type'=>'natural']);
        $response=$this->actingAs($user)->post(route('brand.products.store'),[
            'name'=>'Produit','sku'=>'PRO-1','category'=>'T-shirt','materials'=>[$material->id=>90],
        ]);
        $response->assertSessionHasErrors('materials');
        $this->assertDatabaseMissing('products',['sku'=>'PRO-1']);
    }

    public function test_complete_product_can_be_submitted_and_versioned(): void
    {
        $user=$this->brandUser();$material=Material::create(['name'=>'Lin test','type'=>'natural']);
        $product=Product::create([
            'brand_id'=>$user->brand->id,'name'=>'Chemise','slug'=>'chemise','brand'=>$user->brand->name,'sku'=>'CHEM-1',
            'barcode'=>'123456789','category'=>'Chemise','description'=>'Description complète','origin'=>'Tunisie',
            'image_path'=>'products/demo.jpg','recyclability_score'=>85,'recycling_instructions'=>'Réemploi puis collecte.',
            'status'=>'draft','is_active'=>false,
        ]);
        $product->materials()->attach($material->id,['percentage'=>100,'component'=>'main']);
        $analysis=app(ProductCompletenessService::class)->refresh($product);
        $this->assertTrue($analysis['ready']);
        app(ProductWorkflowService::class)->submit($product,$user->id);
        $this->assertSame('review',$product->fresh()->status);
        $this->assertDatabaseHas('product_versions',['product_id'=>$product->id,'version'=>1]);
    }

    public function test_admin_approval_publishes_product_and_notifies_brand(): void
    {
        $brandUser=$this->brandUser();$admin=User::factory()->create(['role'=>'admin']);
        $material=Material::create(['name'=>'Chanvre test','type'=>'natural']);
        $product=Product::create([
            'brand_id'=>$brandUser->brand->id,'name'=>'Veste','slug'=>'veste','brand'=>$brandUser->brand->name,'sku'=>'V-1',
            'barcode'=>'999','category'=>'Veste','description'=>'Description','origin'=>'France','image_path'=>'products/v.jpg',
            'recyclability_score'=>90,'recycling_instructions'=>'Réparer et collecter.','status'=>'review','is_active'=>false,'completion_score'=>100,
        ]);
        $product->materials()->attach($material->id,['percentage'=>100,'component'=>'main']);
        app(ProductWorkflowService::class)->saveVersion($product,$brandUser->id,'Soumission');
        $this->actingAs($admin)->patch(route('admin.products.approve',$product))->assertRedirect();
        $this->assertSame('published',$product->fresh()->status);
        $this->assertDatabaseHas('brand_notifications',['user_id'=>$brandUser->id,'type'=>'product_approved']);
    }

    public function test_latest_evidence_version_controls_weighted_progress(): void
    {
        $user=$this->brandUser();$label=Label::create(['name'=>'Test','slug'=>'test','pillar'=>'circularity','description'=>'Test']);
        $step=$label->steps()->create(['title'=>'Étape','position'=>1,'weight'=>100]);
        $assessment=Assessment::create(['user_id'=>$user->id,'label_id'=>$label->id,'organization_name'=>'A','framework'=>'Test','status'=>'draft','assessment_date'=>today()]);
        Evidence::create(['assessment_id'=>$assessment->id,'label_step_id'=>$step->id,'title'=>'Ancienne','type'=>'declaration','status'=>'approved','version'=>1]);
        Evidence::create(['assessment_id'=>$assessment->id,'label_step_id'=>$step->id,'title'=>'Nouvelle','type'=>'declaration','status'=>'pending','version'=>2]);
        app(AssessmentProgressService::class)->refresh($assessment);
        $this->assertSame(0,$assessment->fresh()->score);
    }

    public function test_deleting_product_uses_recoverable_soft_delete(): void
    {
        $user=$this->brandUser();
        $product=Product::create(['brand_id'=>$user->brand->id,'name'=>'Test','slug'=>'test','brand'=>$user->brand->name,'sku'=>'DEL-1','category'=>'Test']);
        $this->actingAs($user)->delete(route('brand.products.destroy',$product))->assertRedirect();
        $this->assertSoftDeleted($product);
    }
}
