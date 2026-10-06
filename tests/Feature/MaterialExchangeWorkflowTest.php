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
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\MaterialExchangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialExchangeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_verification_matches_return_with_brand_demand(): void
    {
        [$consumer, $admin, $brandUser, $return, $request] = $this->data();

        app(MaterialExchangeService::class)->adminReview($return, $admin, 'verified', [
            'condition' => 'damaged',
            'confirmed_weight_kg' => 1.2,
            'admin_review_note' => 'Composition et poids vérifiés.',
        ]);

        $this->assertDatabaseHas('product_returns', [
            'id' => $return->id,
            'status' => 'marketplace',
            'admin_review_status' => 'verified',
        ]);
        $this->assertDatabaseHas('material_matches', [
            'material_request_id' => $request->id,
            'product_return_id' => $return->id,
            'status' => 'suggested',
        ]);
        $this->assertDatabaseHas('brand_notifications', [
            'user_id' => $brandUser->id,
            'type' => 'material_match',
        ]);
    }

    public function test_complete_transfer_validates_consumer_points(): void
    {
        [$consumer, $admin, $brandUser, $return, $request] = $this->data();
        $exchange = app(MaterialExchangeService::class);
        $exchange->adminReview($return, $admin, 'verified', [
            'condition' => 'damaged',
            'confirmed_weight_kg' => 1.2,
        ]);
        $match = MaterialMatch::firstOrFail();

        $match = $exchange->accept($match);
        $this->assertSame('reserved_by_brand', $return->fresh()->status);
        $this->assertSame(175, $return->rewardTransaction()->firstOrFail()->points);

        $match = $exchange->transfer($match, $consumer);
        $match = $exchange->receive($match);
        $exchange->verify($match, 'Matière conforme.');

        $this->assertDatabaseHas('product_returns', [
            'id' => $return->id,
            'status' => 'completed',
            'points_awarded' => 175,
        ]);
        $this->assertDatabaseHas('reward_transactions', [
            'product_return_id' => $return->id,
            'status' => 'validated',
            'points' => 175,
        ]);
        $this->assertDatabaseHas('consumer_notifications', [
            'user_id' => $consumer->id,
            'type' => 'reward_validated',
        ]);
    }

    private function data(): array
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $brandUser = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create([
            'user_id' => $brandUser->id,
            'name' => 'Circular Brand',
            'slug' => 'circular-brand',
            'is_active' => true,
        ]);

        $material = Material::create([
            'name' => 'Coton recyclé',
            'type' => 'recycled',
            'recyclable' => true,
        ]);
        $product = Product::create([
            'name' => 'Jean coton',
            'slug' => 'jean-coton',
            'brand' => 'Circular Brand',
            'sku' => 'CB-JEAN-1',
            'category' => 'Pantalon',
            'status' => 'published',
            'is_active' => true,
            'recyclable' => true,
        ]);
        $product->materials()->attach($material->id, ['percentage' => 85]);

        $program = RecoveryProgram::create([
            'name' => 'Collecte textile',
            'organization_name' => 'Centre',
            'type' => 'recycling',
            'reward_points' => 100,
            'is_active' => true,
        ]);
        $point = CollectionPoint::create([
            'recovery_program_id' => $program->id,
            'name' => 'Centre Tunis',
            'address' => '1 rue circulaire',
            'city' => 'Tunis',
            'is_active' => true,
        ]);
        $return = ProductReturn::create([
            'user_id' => $consumer->id,
            'product_id' => $product->id,
            'collection_point_id' => $point->id,
            'reference' => 'TTC-EXCHANGE-1',
            'qr_token' => '00000000-0000-4000-8000-000000000001',
            'status' => 'received',
            'action' => 'recycling',
            'condition' => 'damaged',
            'weight_kg' => 1.1,
        ]);
        RewardTransaction::create([
            'user_id' => $consumer->id,
            'product_return_id' => $return->id,
            'points' => 100,
            'status' => 'pending',
            'description' => 'Récompense en attente',
        ]);
        $request = MaterialRequest::create([
            'brand_id' => $brand->id,
            'title' => 'Coton recyclé recherché',
            'material_name' => 'coton',
            'minimum_percentage' => 70,
            'categories' => ['Pantalon'],
            'accepted_conditions' => ['damaged'],
            'quantity_kg' => 50,
            'remaining_kg' => 50,
            'reward_points' => 75,
            'status' => 'open',
        ]);

        return [$consumer, $admin, $brandUser, $return, $request];
    }
}
