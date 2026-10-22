<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CollectionPoint;
use App\Models\Material;
use App\Models\MaterialMatch;
use App\Models\MaterialRequest;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\User;
use App\Services\MaterialMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiMaterialCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_request_analyzes_existing_returns_and_explains_both_verdicts(): void
    {
        config(['services.gemini.enabled' => false]);
        $brandUser = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $brandUser->id, 'name' => 'Primark', 'slug' => 'primark', 'is_active' => true]);
        $consumer = User::factory()->create(['role' => 'consumer']);

        $compatible = $this->textile($consumer, 'Coton recyclé', 85, 'Pantalon', 'damaged', 'TTC-AI-YES');
        $incompatible = $this->textile($consumer, 'Polyester', 100, 'Pantalon', 'damaged', 'TTC-AI-NO');
        $request = MaterialRequest::create([
            'brand_id' => $brand->id, 'title' => 'Coton Primark', 'material_name' => 'coton',
            'minimum_percentage' => 70, 'categories' => ['Pantalon'], 'accepted_conditions' => ['damaged'],
            'quantity_kg' => 20, 'remaining_kg' => 20, 'reward_points' => 50, 'status' => 'open',
        ]);

        $count = app(MaterialMatchingService::class)->matchRequest($request);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('material_matches', ['product_return_id' => $compatible->id, 'ai_verdict' => 'compatible', 'status' => 'suggested']);
        $this->assertDatabaseHas('material_matches', ['product_return_id' => $incompatible->id, 'ai_verdict' => 'incompatible', 'status' => 'incompatible']);
        $this->assertNotNull(MaterialMatch::where('product_return_id', $compatible->id)->value('ai_reason'));
    }

    private function textile(User $consumer, string $materialName, float $percentage, string $category, string $condition, string $reference): ProductReturn
    {
        $program = RecoveryProgram::firstOrCreate(
            ['name' => 'Programme test IA'],
            ['organization_name' => 'TexTileCycle', 'type' => 'recycling', 'description' => 'Collecte utilisée par les tests de compatibilité.']
        );
        $point = CollectionPoint::firstOrCreate(
            ['name' => 'Point test IA'],
            ['recovery_program_id' => $program->id, 'address' => '1 rue du Test', 'city' => 'Paris', 'is_active' => true]
        );
        $material = Material::create(['name' => $materialName, 'type' => 'recycled', 'recyclable' => true]);
        $product = Product::create([
            'name' => $reference, 'slug' => strtolower($reference), 'brand' => 'Primark', 'sku' => $reference,
            'category' => $category, 'status' => 'published', 'is_active' => true, 'recyclable' => true,
        ]);
        $product->materials()->attach($material->id, ['percentage' => $percentage]);

        return ProductReturn::create([
            'user_id' => $consumer->id, 'product_id' => $product->id, 'collection_point_id' => $point->id, 'reference' => $reference,
            'qr_token' => fake()->uuid(), 'status' => 'marketplace', 'action' => 'recycling',
            'condition' => $condition, 'weight_kg' => 1, 'admin_review_status' => 'verified',
            'admin_verified_at' => now(),
        ]);
    }
}
