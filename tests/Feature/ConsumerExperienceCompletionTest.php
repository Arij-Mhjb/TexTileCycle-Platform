<?php

namespace Tests\Feature;

use App\Models\CollectionPoint;
use App\Models\ConsumerClaim;
use App\Models\MarketplaceListing;
use App\Models\RecoveryProgram;
use App\Models\RepairCenter;
use App\Models\RepairOrder;
use App\Models\RewardItem;
use App\Models\RewardTransaction;
use App\Models\TradeInRequest;
use App\Models\User;
use App\Services\RepairMarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConsumerExperienceCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_redeems_an_available_reward_without_overspending(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        RewardTransaction::create([
            'user_id' => $consumer->id,
            'type' => 'return_reward',
            'points' => 100,
            'status' => 'validated',
            'description' => 'Retour validé',
            'validated_at' => now(),
        ]);
        $reward = RewardItem::create(['name' => 'Bon atelier', 'points_cost' => 40, 'stock' => 1, 'is_active' => true]);

        $this->actingAs($consumer)->post(route('consumer.rewards.redeem', $reward))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reward_redemptions', [
            'user_id' => $consumer->id,
            'reward_item_id' => $reward->id,
            'points_spent' => 40,
            'status' => 'issued',
        ]);
        $this->assertDatabaseHas('reward_transactions', ['user_id' => $consumer->id, 'points' => -40, 'status' => 'spent']);
        $this->assertSame(0, $reward->fresh()->stock);

        $second = RewardItem::create(['name' => 'Bon premium', 'points_cost' => 70, 'is_active' => true]);
        $this->post(route('consumer.rewards.redeem', $second))->assertSessionHasErrors('reward');
        $this->assertSame(1, $consumer->rewardRedemptions()->count());

        $admin = User::factory()->create(['role' => 'admin']);
        $code = $consumer->rewardRedemptions()->firstOrFail()->code;
        $this->actingAs($admin)->post(route('admin.reward-redemptions.consume'), ['code' => $code])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reward_redemptions', ['code' => $code, 'status' => 'used']);
        $this->post(route('admin.reward-redemptions.consume'), ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_claim_can_only_reference_the_consumers_own_journey(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $other = User::factory()->create(['role' => 'consumer']);
        $foreignTradeIn = TradeInRequest::factory()->create(['user_id' => $other->id]);

        $payload = [
            'category' => 'trade_in',
            'subject' => 'Ma reprise est bloquée',
            'description' => 'Je souhaite obtenir une vérification détaillée de cette reprise.',
            'trade_in_request_id' => $foreignTradeIn->id,
        ];
        $this->actingAs($consumer)->post(route('consumer.claims.store'), $payload)->assertSessionHasErrors('trade_in_request_id');
        $this->assertSame(0, ConsumerClaim::count());

        $ownTradeIn = TradeInRequest::factory()->create(['user_id' => $consumer->id]);
        $this->post(route('consumer.claims.store'), [...$payload, 'trade_in_request_id' => $ownTradeIn->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('consumer_claims', ['user_id' => $consumer->id, 'trade_in_request_id' => $ownTradeIn->id, 'status' => 'open']);
    }

    public function test_repair_cannot_start_until_the_consumer_accepts_the_quote(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $center = RepairCenter::create(['name' => 'Atelier test', 'city' => 'Tunis']);
        $order = RepairOrder::create([
            'reference' => 'REP-TEST-1',
            'user_id' => $consumer->id,
            'issue_description' => 'Fermeture cassée',
            'decision' => 'repair',
            'status' => 'diagnosed',
            'consumer_decision' => 'pending',
            'repair_center_id' => $center->id,
        ]);
        $service = app(RepairMarketplaceService::class);

        try {
            $service->startRepair($order);
            $this->fail('La réparation aurait dû attendre l’accord du consommateur.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertSame('diagnosed', $order->fresh()->status);
        }

        $this->actingAs($consumer)->post(route('consumer.repairs.respond', $order), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $service->startRepair($order->fresh());
        $this->assertSame('in_repair', $order->fresh()->status);
    }

    public function test_accepted_marketplace_offer_creates_a_buyer_order_and_receipt_flow(): void
    {
        $buyer = User::factory()->create(['role' => 'consumer']);
        $other = User::factory()->create(['role' => 'consumer']);
        $program = RecoveryProgram::create(['name' => 'Retrait', 'organization_name' => 'Org', 'type' => 'recycling', 'city' => 'Tunis', 'is_active' => true]);
        $point = CollectionPoint::create(['recovery_program_id' => $program->id, 'name' => 'Point central', 'address' => 'Rue 1', 'city' => 'Tunis', 'is_active' => true]);
        $listing = MarketplaceListing::factory()->create(['status' => 'published', 'price' => 80]);
        $service = app(RepairMarketplaceService::class);
        $offer = $service->makeOffer($listing, $buyer, 70, 'Je suis intéressé.');
        $service->respondToOffer($offer, true);
        $order = $listing->fresh()->order;

        $this->assertNotNull($order);
        $this->actingAs($other)->post(route('consumer.marketplace.checkout', $order), [
            'fulfilment_method' => 'pickup', 'collection_point_id' => $point->id, 'payment_method' => 'pay_on_pickup',
        ])->assertForbidden();

        $this->actingAs($buyer)->post(route('consumer.marketplace.checkout', $order), [
            'fulfilment_method' => 'pickup', 'collection_point_id' => $point->id, 'payment_method' => 'pay_on_pickup',
        ])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);

        $service->markSold($listing->fresh());
        $this->assertSame('ready', $order->fresh()->status);
        $this->post(route('consumer.marketplace.received', $order))->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_consumer_can_export_only_their_personal_journey(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer', 'name' => 'Export Consumer']);
        $other = User::factory()->create(['role' => 'consumer']);
        TradeInRequest::factory()->create(['user_id' => $consumer->id, 'product_name' => 'Mon manteau']);
        TradeInRequest::factory()->create(['user_id' => $other->id, 'product_name' => 'Article privé tiers']);

        $response = $this->actingAs($consumer)->get(route('consumer.data.export'));

        $response->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Export Consumer', $content);
        $this->assertStringContainsString('Mon manteau', $content);
        $this->assertStringNotContainsString('Article privé tiers', $content);
    }

    public function test_consumer_can_buy_one_marketplace_item_with_points(): void
    {
        $buyer = User::factory()->create(['role' => 'consumer']);
        $other = User::factory()->create(['role' => 'consumer']);
        foreach ([$buyer, $other] as $consumer) {
            RewardTransaction::create([
                'user_id' => $consumer->id, 'type' => 'return_reward', 'points' => 100,
                'status' => 'validated', 'description' => 'Retour validé', 'validated_at' => now(),
            ]);
        }
        $listing = MarketplaceListing::factory()->create(['status' => 'published', 'points_price' => 60]);

        $this->actingAs($buyer)->post(route('consumer.marketplace.buy-with-points', $listing), [
            'fulfilment_method' => 'delivery',
            'delivery_address' => '12 rue de la Circularité, Tunis',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('marketplace_orders', [
            'marketplace_listing_id' => $listing->id, 'buyer_id' => $buyer->id,
            'points_spent' => 60, 'payment_method' => 'points', 'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('reward_transactions', [
            'user_id' => $buyer->id, 'type' => 'marketplace_purchase', 'points' => -60, 'status' => 'spent',
        ]);
        $this->assertSame('reserved', $listing->fresh()->status);

        $this->actingAs($other)->post(route('consumer.marketplace.buy-with-points', $listing), [
            'fulfilment_method' => 'delivery', 'delivery_address' => '20 rue de la Réutilisation, Tunis',
        ])->assertSessionHasErrors('status');
        $this->assertSame(1, $listing->order()->count());
    }

    public function test_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $consumer = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($consumer)->patch(route('profile.update'), [
            'name' => $consumer->name,
            'email' => $consumer->email,
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ])->assertSessionHasNoErrors();

        $path = $consumer->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }
}
