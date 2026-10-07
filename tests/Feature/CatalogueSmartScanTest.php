<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Brand;
use App\Models\Evidence;
use App\Models\Label;
use App\Models\Product;
use App\Models\User;
use App\Services\AssessmentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueSmartScanTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\BrandQuestionnaireData;

    public function test_brand_registration_creates_profile_and_redirects(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Notification::fake();
        $response=$this->post('/register',[
            'name'=>'Responsable','email'=>'newbrand@example.test','role'=>'brand','brand_name'=>'Nouvelle Marque',
            'password'=>'password','password_confirmation'=>'password',
            'answers'=>$this->brandAnswers(), 'analysis_consent'=>'1',
        ]);
        $user=User::where('email','newbrand@example.test')->firstOrFail();
        $response->assertRedirect(route('brand.application-pending'));
        $this->assertGuest();
        $this->assertTrue($user->isBrand());
        $this->assertDatabaseHas('brands', ['user_id' => $user->id, 'name' => 'Nouvelle Marque']);
    }

    public function test_consumer_can_identify_product_by_barcode(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $product = Product::create([
            'name' => 'T-shirt circulaire', 'slug' => 't-shirt-circulaire', 'brand' => 'Démo', 'sku' => 'SKU-1', 'barcode' => '6191234567890',
            'category' => 'T-shirt', 'status' => 'published', 'is_active' => true,
        ]);
        $this->actingAs($user)->post(route('scans.store'), ['source_type' => 'barcode', 'value' => '6191234567890'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('scans', ['user_id' => $user->id, 'product_id' => $product->id, 'status' => 'completed']);
    }

    public function test_brand_cannot_edit_another_brands_product(): void
    {
        $a = User::factory()->create(['role' => 'brand']);
        $b = User::factory()->create(['role' => 'brand']);
        $brandA = Brand::create(['user_id' => $a->id, 'name' => 'A', 'slug' => 'a']);
        Brand::create(['user_id' => $b->id, 'name' => 'B', 'slug' => 'b']);
        $product = Product::create(['brand_id' => $brandA->id, 'name' => 'Produit A', 'slug' => 'produit-a', 'brand' => 'A', 'sku' => 'A-1', 'category' => 'Test']);
        $this->actingAs($b)->get(route('brand.products.edit', $product))->assertForbidden();
    }

    public function test_approved_evidences_compute_progress_without_certification_claim(): void
    {
        $user = User::factory()->create(['role' => 'brand']);
        $label = Label::create(['name' => 'Référentiel test', 'slug' => 'referentiel-test', 'pillar' => 'circularity', 'description' => 'Test']);
        $one = $label->steps()->create(['title' => 'Étape 1', 'position' => 1]);
        $label->steps()->create(['title' => 'Étape 2', 'position' => 2]);
        $assessment = Assessment::create(['user_id' => $user->id, 'label_id' => $label->id, 'organization_name' => 'Marque', 'framework' => 'Test', 'status' => 'draft', 'assessment_date' => today()]);
        Evidence::create(['assessment_id' => $assessment->id, 'label_step_id' => $one->id, 'title' => 'Preuve', 'type' => 'declaration', 'status' => 'approved']);
        app(AssessmentProgressService::class)->refresh($assessment);
        $this->assertSame(50, $assessment->fresh()->score);
        $this->assertNotSame('certified', $assessment->fresh()->status);
    }
}
