<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CollectionPoint;
use App\Models\Detection;
use App\Models\Evidence;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\Scan;
use App\Models\TreatmentResult;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TexTileCycleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_displays_scores_and_identifies_illustrative_photos(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        Product::create([
            'name' => 'Veste circulaire', 'brand' => 'Atelier', 'sku' => 'DESIGN-001',
            'category' => 'Veste', 'status' => 'published', 'circularity_score' => 82,
        ]);

        $this->actingAs($consumer)->get(route('consumer.products'))
            ->assertOk()
            ->assertSee('Veste circulaire')
            ->assertSee('82/100')
            ->assertSee('Non évalué')
            ->assertSee('Photo illustrative')
            ->assertSee('Navigation mobile');
    }

    public function test_public_landing_page_and_authentication_pages_are_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('TexTileCycle')
            ->assertSee('images/brand/textilecycle-logo.png', false)
            ->assertSee('images/story/clothes-sorting.jpg', false)
            ->assertSee('images/impact/clean-energy.jpg', false)
            ->assertSee('images/odd/odd-12.png', false)
            ->assertSee('textilecycle-theme', false)
            ->assertSee('Photos : Julia M Cameron, Burcu, Adrinil Dennis');

        $this->get('/login')->assertOk()->assertSee('textilecycle-theme', false);
        $this->get('/register')->assertOk();
    }

    public function test_admin_can_create_a_product_and_link_materials(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);
        $material = Material::create([
            'name' => 'Coton recyclé',
            'type' => 'recycled',
            'recyclable' => true,
        ]);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Circular Shirt',
            'brand' => 'EcoWear',
            'sku' => 'EW-CS-001',
            'category' => 'Chemise',
            'status' => 'published',
            'year' => 2026,
            'repairable' => 1,
            'reusable' => 1,
            'recyclable' => 1,
            'environmental_score' => 82,
            'circularity_score' => 91,
            'animal_free_score' => 100,
            'traceability_score' => 76,
            'material_ids' => [$material->id],
            'image' => new UploadedFile(
                public_path('images/impact/responsible-production.jpg'),
                'circular-shirt.jpg',
                'image/jpeg',
                null,
                true,
            ),
        ]);

        $response->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'EW-CS-001')->firstOrFail();
        $this->assertTrue($product->materials->contains($material));
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_dashboard_redirects_each_role_to_its_own_experience(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $consumer = User::factory()->create(['role' => 'consumer']);
        $this->actingAs($consumer)->get('/dashboard')->assertRedirect(route('consumer.home'));
        $this->get(route('consumer.home'))->assertOk()->assertSee('Quel vêtement voulez-vous sauver');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Circularity Intelligence Center');
    }

    public function test_consumer_cannot_access_administration_crud(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($consumer)->get(route('products.index'))->assertForbidden();
        $this->get(route('materials.index'))->assertForbidden();
        $this->get(route('assessments.index'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_consumer_only_sees_owned_scans(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $other = User::factory()->create(['role' => 'consumer']);
        $own = Scan::create(['user_id' => $consumer->id, 'source_type' => 'label', 'status' => 'pending']);
        $foreign = Scan::create(['user_id' => $other->id, 'source_type' => 'ticket', 'status' => 'pending']);

        $this->actingAs($consumer)->get(route('scans.index'))
            ->assertOk()->assertSee('#'.$own->id)->assertDontSee('#'.$foreign->id);
        $this->get(route('scans.edit', $foreign))->assertForbidden();
    }

    public function test_every_crud_index_create_and_edit_page_renders(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'demo@textilecycle.test')->firstOrFail());

        $resources = [
            'products' => Product::first(),
            'materials' => Material::first(),
            'scans' => Scan::first(),
            'detections' => Detection::first(),
            'recovery-programs' => RecoveryProgram::first(),
            'collection-points' => CollectionPoint::first(),
            'product-returns' => ProductReturn::first(),
            'treatment-results' => TreatmentResult::first(),
            'assessments' => Assessment::first(),
            'evidences' => Evidence::first(),
        ];

        foreach ($resources as $route => $model) {
            $this->get(route($route.'.index'))->assertOk();
            $this->get(route($route.'.create'))->assertOk();
            $this->get(route($route.'.edit', $model))->assertOk();
        }
    }
}
