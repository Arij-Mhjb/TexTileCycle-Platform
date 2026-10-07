<?php

namespace Tests\Feature;

use App\Models\CollectionPoint;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\User;
use App\Services\ProductReturnWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConsumerJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_creates_traceable_return_with_pending_reward(): void
    {
        [$user, $product, $point] = $this->journeyData();

        $this->actingAs($user)->post(route('product-returns.store'), [
            'product_id' => $product->id,
            'collection_point_id' => $point->id,
            'action' => 'recycling',
            'condition' => 'unusable',
            'weight_kg' => 1.5,
            'returned_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $return = ProductReturn::firstOrFail();
        $this->assertSame('dropoff_expected', $return->status);
        $this->assertNotNull($return->qr_token);
        $this->assertStringStartsWith('TTC-', $return->reference);
        $this->assertSame(2, $return->statusHistory()->count());
        $this->assertDatabaseHas('reward_transactions', [
            'product_return_id' => $return->id,
            'points' => 80,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('product-returns.show', $return))
            ->assertOk()
            ->assertSee($return->reference);
    }

    public function test_reward_is_validated_only_after_complete_verified_workflow(): void
    {
        [$user, $product, $point] = $this->journeyData();
        $workflow = app(ProductReturnWorkflowService::class);
        $return = $workflow->create($user, [
            'product_id' => $product->id,
            'collection_point_id' => $point->id,
            'action' => 'recycling',
            'condition' => 'unusable',
        ]);

        foreach (['dropoff_expected', 'received', 'inspected', 'routed', 'completed'] as $status) {
            $return = $workflow->transition($return, $status, $user, 'Étape test');
        }

        $this->assertSame('completed', $return->status);
        $this->assertSame(80, $return->fresh()->points_awarded);
        $this->assertDatabaseHas('reward_transactions', [
            'product_return_id' => $return->id,
            'status' => 'validated',
            'points' => 80,
        ]);
    }

    public function test_consumer_cannot_edit_return_after_reception(): void
    {
        [$user, $product, $point] = $this->journeyData();
        $return = ProductReturn::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'collection_point_id' => $point->id,
            'reference' => 'TTC-LOCKED',
            'status' => 'received',
            'action' => 'recycling',
        ]);

        $this->actingAs($user)->get(route('product-returns.edit', $return))->assertForbidden();
        $this->delete(route('product-returns.destroy', $return))->assertForbidden();
    }

    public function test_product_passport_explains_recommended_second_life(): void
    {
        [$user, $product] = $this->journeyData();

        $this->actingAs($user)
            ->get(route('consumer.products.show', $product))
            ->assertOk()
            ->assertSee('Meilleure seconde vie proposée')
            ->assertSee('Recycler dans une filière adaptée')
            ->assertSee('ne constituent pas une certification');
    }

    public function test_return_rejects_incompatible_collection_program(): void
    {
        [$user, $product] = $this->journeyData();
        $repairProgram = RecoveryProgram::create([
            'name' => 'Atelier',
            'organization_name' => 'Partenaire',
            'type' => 'repair',
            'reward_points' => 20,
            'is_active' => true,
        ]);
        $repairPoint = CollectionPoint::create([
            'recovery_program_id' => $repairProgram->id,
            'name' => 'Atelier test',
            'address' => '1 rue test',
            'city' => 'Tunis',
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(ProductReturnWorkflowService::class)->create($user, [
            'product_id' => $product->id,
            'collection_point_id' => $repairPoint->id,
            'action' => 'recycling',
        ]);
    }

    public function test_revising_dropoff_recalculates_pending_reward(): void
    {
        [$user, $product, $point] = $this->journeyData();
        $workflow = app(ProductReturnWorkflowService::class);
        $return = $workflow->create($user, [
            'product_id' => $product->id,
            'collection_point_id' => $point->id,
            'action' => 'recycling',
        ]);

        $secondProgram = RecoveryProgram::create([
            'name' => 'Recyclage premium',
            'organization_name' => 'Partenaire premium',
            'type' => 'recycling',
            'reward_points' => 120,
            'is_active' => true,
        ]);
        $secondPoint = CollectionPoint::create([
            'recovery_program_id' => $secondProgram->id,
            'name' => 'Point premium',
            'address' => '2 rue test',
            'city' => 'Tunis',
            'is_active' => true,
        ]);

        $workflow->revise($return, [
            'product_id' => $product->id,
            'collection_point_id' => $secondPoint->id,
            'action' => 'recycling',
        ], $user);

        $this->assertSame(120, $return->rewardTransaction()->firstOrFail()->points);
    }

    public function test_consumer_cancellation_preserves_audit_trail(): void
    {
        [$user, $product, $point] = $this->journeyData();
        $workflow = app(ProductReturnWorkflowService::class);
        $return = $workflow->create($user, [
            'product_id' => $product->id,
            'collection_point_id' => $point->id,
            'action' => 'recycling',
        ]);
        $workflow->transition($return, 'dropoff_expected', $user);

        $this->actingAs($user)
            ->delete(route('product-returns.destroy', $return))
            ->assertRedirect(route('product-returns.index'));

        $this->assertDatabaseHas('product_returns', ['id' => $return->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('reward_transactions', [
            'product_return_id' => $return->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('return_status_histories', [
            'product_return_id' => $return->id,
            'status' => 'cancelled',
        ]);
    }

    private function journeyData(): array
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $product = Product::create([
            'name' => 'Jean circulaire',
            'slug' => 'jean-circulaire',
            'brand' => 'Eco Denim',
            'sku' => 'ED-1',
            'barcode' => '619000000001',
            'category' => 'Jean',
            'status' => 'published',
            'is_active' => true,
            'recyclable' => true,
            'reusable' => true,
            'repairable' => true,
        ]);
        $program = RecoveryProgram::create([
            'name' => 'Recyclage textile',
            'organization_name' => 'Centre circulaire',
            'type' => 'recycling',
            'reward_points' => 80,
            'is_active' => true,
        ]);
        $point = CollectionPoint::create([
            'recovery_program_id' => $program->id,
            'name' => 'Point Lac',
            'address' => '10 avenue du Lac',
            'city' => 'Tunis',
            'is_active' => true,
        ]);

        return [$user, $product, $point];
    }
}

