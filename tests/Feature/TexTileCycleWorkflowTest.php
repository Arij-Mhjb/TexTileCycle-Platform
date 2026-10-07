<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Product;
use App\Models\Scan;
use App\Models\User;
use App\Services\MediaStorageService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class TexTileCycleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_displays_published_active_products_and_recyclability(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        Product::create(['name' => 'Veste circulaire', 'brand' => 'Atelier', 'sku' => 'DESIGN-001', 'category' => 'Veste', 'status' => 'published', 'is_active' => true, 'recyclability_score' => 82]);
        $this->actingAs($consumer)->get(route('consumer.products'))->assertOk()->assertSee('Veste circulaire')->assertSee('82/100')->assertSee('Origine non renseignée');
    }

    public function test_public_landing_page_and_authentication_pages_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('TexTileCycle')->assertSee('images/brand/textilecycle-logo.png', false)->assertSee('textilecycle-theme', false);
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk()->assertSee('Type de compte');
    }

    public function test_admin_can_create_a_product_and_link_materials(): void
    {
        $media = Mockery::mock(MediaStorageService::class);
        $media->shouldReceive('storeProductImage')->once()->andReturn([
            'path' => 'textilecycle/products/test-image', 'storage_disk' => 'cloudinary',
            'cloudinary_public_id' => 'textilecycle/products/test-image', 'secure_url' => 'https://res.cloudinary.com/demo/image/upload/test-image.jpg',
            'delivery_type' => 'upload', 'format' => 'jpg', 'version' => 1,
        ]);
        $this->app->instance(MediaStorageService::class, $media);
        $user = User::factory()->create(['role' => 'admin']);
        $material = Material::create(['name' => 'Coton recyclé', 'type' => 'recycled', 'recyclable' => true]);
        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Circular Shirt', 'brand' => 'EcoWear', 'sku' => 'EW-CS-001', 'category' => 'Chemise', 'status' => 'published', 'year' => 2026,
            'repairable' => 1, 'reusable' => 1, 'recyclable' => 1, 'environmental_score' => 82, 'circularity_score' => 91,
            'animal_free_score' => 100, 'traceability_score' => 76, 'material_ids' => [$material->id],
            'image' => new UploadedFile(public_path('images/impact/responsible-production.jpg'), 'circular-shirt.jpg', 'image/jpeg', null, true),
        ]);
        $response->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'EW-CS-001')->firstOrFail();
        $this->assertTrue($product->materials->contains($material));
        $this->assertSame('cloudinary', $product->image_storage_disk);
        $this->assertSame('textilecycle/products/test-image', $product->image_cloudinary_public_id);
    }

    public function test_dashboard_redirects_each_role_to_its_own_experience(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $consumer = User::factory()->create(['role' => 'consumer']);
        $this->actingAs($consumer)->get('/dashboard')->assertRedirect(route('consumer.home'));
        $this->get(route('consumer.home'))->assertOk();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_consumer_cannot_access_administration_crud(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $this->actingAs($consumer)->get(route('products.index'))->assertForbidden();
        $this->get(route('materials.index'))->assertForbidden();
        $this->get(route('assessments.index'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_consumer_can_only_open_owned_scan_results(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $other = User::factory()->create(['role' => 'consumer']);
        $own = Scan::create(['user_id' => $consumer->id, 'source_type' => 'manual', 'status' => 'not_found', 'scanned_at' => now()]);
        $foreign = Scan::create(['user_id' => $other->id, 'source_type' => 'manual', 'status' => 'not_found', 'scanned_at' => now()]);
        $this->actingAs($consumer)->get(route('scans.show', $own))->assertOk();
        $this->get(route('scans.show', $foreign))->assertForbidden();
    }

    public function test_administration_pages_render_with_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@textilecycle.test')->firstOrFail());
        foreach (['products', 'materials', 'detections', 'recovery-programs', 'collection-points', 'product-returns', 'treatment-results', 'assessments', 'evidences'] as $route) {
            $this->get(route($route.'.index'))->assertOk();
        }
        $this->get(route('scans.index'))->assertOk();
        $this->get(route('admin.labels.index'))->assertOk();
        $this->get(route('products.edit', Product::first()))->assertOk();
    }
}
