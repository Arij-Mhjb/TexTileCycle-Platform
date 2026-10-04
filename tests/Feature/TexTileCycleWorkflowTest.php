<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TexTileCycleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_and_authentication_pages_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('TexTileCycle');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_authenticated_user_can_create_a_product_and_link_materials(): void
    {
        $user = User::factory()->create();
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
        ]);

        $response->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'EW-CS-001')->firstOrFail();
        $this->assertTrue($product->materials->contains($material));
    }

    public function test_dashboard_requires_authentication_and_renders_for_a_user(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pilotage de la circularité');
    }

    public function test_every_crud_index_create_and_edit_page_renders(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'demo@textilecycle.test')->firstOrFail());

        $resources = [
            'products' => \App\Models\Product::first(),
            'materials' => \App\Models\Material::first(),
            'scans' => \App\Models\Scan::first(),
            'detections' => \App\Models\Detection::first(),
            'recovery-programs' => \App\Models\RecoveryProgram::first(),
            'collection-points' => \App\Models\CollectionPoint::first(),
            'product-returns' => \App\Models\ProductReturn::first(),
            'treatment-results' => \App\Models\TreatmentResult::first(),
            'assessments' => \App\Models\Assessment::first(),
            'evidences' => \App\Models\Evidence::first(),
        ];

        foreach ($resources as $route => $model) {
            $this->get(route($route.'.index'))->assertOk();
            $this->get(route($route.'.create'))->assertOk();
            $this->get(route($route.'.edit', $model))->assertOk();
        }
    }
}
