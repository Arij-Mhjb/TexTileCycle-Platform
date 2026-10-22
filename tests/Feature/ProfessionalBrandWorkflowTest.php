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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfessionalBrandWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function brandUser(string $name = 'Marque A'): User
    {
        $user = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $user->id, 'name' => $name, 'slug' => str($name)->slug()]);

        return $user->fresh();
    }

    public function test_composition_must_total_exactly_one_hundred_percent(): void
    {
        $user = $this->brandUser();
        $material = Material::create(['name' => 'Coton test', 'type' => 'natural']);
        $response = $this->actingAs($user)->post(route('brand.products.store'), [
            'name' => 'Produit', 'sku' => 'PRO-1', 'category' => 'T-shirt', 'materials' => [$material->id => 90],
        ]);
        $response->assertSessionHasErrors('materials');
        $this->assertDatabaseMissing('products', ['sku' => 'PRO-1']);
    }

    public function test_product_form_uses_accessible_category_and_origin_lists(): void
    {
        $user = $this->brandUser();

        $this->actingAs($user)->get(route('brand.products.create'))
            ->assertOk()
            ->assertSee('Sélectionner une catégorie…')
            ->assertSee('Pays de fabrication')
            ->assertSee('Tunisie')
            ->assertSee('Veste');
    }

    public function test_product_category_and_origin_must_come_from_php_reference_lists(): void
    {
        $user = $this->brandUser();

        $this->actingAs($user)->post(route('brand.products.store'), [
            'name' => 'Produit invalide', 'sku' => 'OPTIONS-INVALID',
            'category' => 'Catégorie inventée', 'origin' => 'Pays inventé',
        ])->assertSessionHasErrors([
            'category' => 'Sélectionnez une catégorie proposée dans la liste.',
            'origin' => 'Sélectionnez un pays d’origine proposé dans la liste.',
        ]);

        $this->assertDatabaseMissing('products', ['sku' => 'OPTIONS-INVALID']);
    }

    public function test_composition_cannot_exceed_one_hundred_percent(): void
    {
        $user = $this->brandUser();
        $cotton = Material::create(['name' => 'Coton test', 'type' => 'natural']);
        $polyester = Material::create(['name' => 'Polyester test', 'type' => 'synthetic']);

        $this->actingAs($user)->post(route('brand.products.store'), [
            'name' => 'Produit', 'sku' => 'PRO-OVER', 'category' => 'T-shirt',
            'materials' => [$cotton->id => 70, $polyester->id => 40],
        ])->assertSessionHasErrors('materials');

        $this->assertDatabaseMissing('products', ['sku' => 'PRO-OVER']);
    }

    public function test_duplicate_sku_has_a_clear_message_and_keeps_the_entered_value(): void
    {
        $user = $this->brandUser();
        Product::create([
            'brand_id' => $user->brand->id,
            'name' => 'Produit existant',
            'slug' => 'produit-existant',
            'brand' => $user->brand->name,
            'sku' => 'DUP-001',
            'category' => 'T-shirt',
        ]);

        $this->actingAs($user)->from(route('brand.products.create'))->post(route('brand.products.store'), [
            'name' => 'Nouveau produit',
            'sku' => 'DUP-001',
            'category' => 'T-shirt',
        ])->assertRedirect(route('brand.products.create'))
            ->assertSessionHasErrors([
                'sku' => 'Cette référence SKU est déjà utilisée par un autre produit. Choisissez une référence différente, par exemple MARQUE-CATEGORIE-001.',
            ])
            ->assertSessionHasInput('sku', 'DUP-001');

        $this->assertSame(1, Product::where('sku', 'DUP-001')->count());
    }

    public function test_brand_product_photo_is_stored_locally_when_cloudinary_is_not_configured(): void
    {
        Storage::fake('public');
        config(['media.product_storage' => 'public']);
        $user = $this->brandUser();

        $this->actingAs($user)->post(route('brand.products.store'), [
            'name' => 'Produit avec photo', 'sku' => 'PHOTO-1', 'category' => 'Veste',
            'image' => UploadedFile::fake()->image('veste.jpg', 600, 600),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'PHOTO-1')->firstOrFail();
        $this->assertSame('public', $product->image_storage_disk);
        $this->assertNull($product->image_cloudinary_public_id);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_oversized_product_photo_has_a_clear_error_message(): void
    {
        $user = $this->brandUser();

        $this->actingAs($user)->post(route('brand.products.store'), [
            'name' => 'Produit photo lourde', 'sku' => 'PHOTO-LARGE', 'category' => 'Veste',
            'image' => UploadedFile::fake()->image('veste.jpg', 1200, 1200)->size(2000),
        ])->assertSessionHasErrors([
            'image' => 'La photo ne doit pas dépasser 1,9 Mo. Les grandes photos sont normalement optimisées automatiquement.',
        ]);

        $this->assertDatabaseMissing('products', ['sku' => 'PHOTO-LARGE']);
    }

    public function test_complete_product_can_be_submitted_and_versioned(): void
    {
        $user = $this->brandUser();
        $material = Material::create(['name' => 'Lin test', 'type' => 'natural']);
        $product = Product::create([
            'brand_id' => $user->brand->id, 'name' => 'Chemise', 'slug' => 'chemise', 'brand' => $user->brand->name, 'sku' => 'CHEM-1',
            'barcode' => '123456789', 'category' => 'Chemise', 'description' => 'Description complète', 'origin' => 'Tunisie',
            'image_path' => 'products/demo.jpg', 'recyclability_score' => 85, 'recycling_instructions' => 'Réemploi puis collecte.',
            'status' => 'draft', 'is_active' => false,
        ]);
        $product->materials()->attach($material->id, ['percentage' => 100, 'component' => 'main']);
        $analysis = app(ProductCompletenessService::class)->refresh($product);
        $this->assertTrue($analysis['ready']);
        app(ProductWorkflowService::class)->submit($product, $user->id);
        $this->assertSame('review', $product->fresh()->status);
        $this->assertDatabaseHas('product_versions', ['product_id' => $product->id, 'version' => 1]);
    }

    public function test_admin_approval_publishes_product_and_notifies_brand(): void
    {
        $brandUser = $this->brandUser();
        $admin = User::factory()->create(['role' => 'admin']);
        $material = Material::create(['name' => 'Chanvre test', 'type' => 'natural']);
        $product = Product::create([
            'brand_id' => $brandUser->brand->id, 'name' => 'Veste', 'slug' => 'veste', 'brand' => $brandUser->brand->name, 'sku' => 'V-1',
            'barcode' => '999', 'category' => 'Veste', 'description' => 'Description', 'origin' => 'France', 'image_path' => 'products/v.jpg',
            'recyclability_score' => 90, 'recycling_instructions' => 'Réparer et collecter.', 'status' => 'review', 'is_active' => false, 'completion_score' => 100,
        ]);
        $product->materials()->attach($material->id, ['percentage' => 100, 'component' => 'main']);
        app(ProductWorkflowService::class)->saveVersion($product, $brandUser->id, 'Soumission');
        $this->actingAs($admin)->patch(route('admin.products.approve', $product))->assertRedirect();
        $this->assertSame('published', $product->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $brandUser->id, 'type' => 'product_approved']);
    }

    public function test_latest_evidence_version_controls_weighted_progress(): void
    {
        $user = $this->brandUser();
        $label = Label::create(['name' => 'Test', 'slug' => 'test', 'pillar' => 'circularity', 'description' => 'Test']);
        $step = $label->steps()->create(['title' => 'Étape', 'position' => 1, 'weight' => 100]);
        $assessment = Assessment::create(['user_id' => $user->id, 'label_id' => $label->id, 'organization_name' => 'A', 'framework' => 'Test', 'status' => 'draft', 'assessment_date' => today()]);
        Evidence::create(['assessment_id' => $assessment->id, 'label_step_id' => $step->id, 'title' => 'Ancienne', 'type' => 'declaration', 'status' => 'approved', 'version' => 1]);
        Evidence::create(['assessment_id' => $assessment->id, 'label_step_id' => $step->id, 'title' => 'Nouvelle', 'type' => 'declaration', 'status' => 'pending', 'version' => 2]);
        app(AssessmentProgressService::class)->refresh($assessment);
        $this->assertSame(0, $assessment->fresh()->score);
    }

    public function test_deleting_product_uses_recoverable_soft_delete(): void
    {
        $user = $this->brandUser();
        $product = Product::create(['brand_id' => $user->brand->id, 'name' => 'Test', 'slug' => 'test', 'brand' => $user->brand->name, 'sku' => 'DEL-1', 'category' => 'Test']);
        $this->actingAs($user)->delete(route('brand.products.destroy', $product))->assertRedirect();
        $this->assertSoftDeleted($product);
    }
}
