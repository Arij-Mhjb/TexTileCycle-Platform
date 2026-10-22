<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CollectionPoint;
use App\Models\Label;
use App\Models\LabelRoadmap;
use App\Models\Material;
use App\Models\MaterialMatch;
use App\Models\MaterialRequest;
use App\Models\MaterialUsage;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\RoadmapSubmission;
use App\Models\User;
use App\Services\MaterialExchangeService;
use App\Services\MediaStorageService;
use App\Services\ProductReturnWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CircularMaterialJourneyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $consumer;

    private User $collector;

    private Product $product;

    private LabelRoadmap $plan;

    private CollectionPoint $point;

    protected function setUp(): void
    {
        parent::setUp();
        config(['roadmaps.proof_storage' => 'local', 'services.cloudinary.url' => null, 'services.cloudinary.cloud_name' => null]);
        Storage::fake('local');
        Storage::fake('public');
        $this->partialMock(MediaStorageService::class, function ($mock) {
            $mock->shouldReceive('storeReturnEvidence')->andReturnUsing(fn ($file, $id, $kind) => [
                'path' => $file->store('returns/'.$id.'/'.$kind, 'public'), 'storage_disk' => 'public',
            ]);
        });
        $this->owner = User::factory()->create(['role' => 'brand']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->consumer = User::factory()->create(['role' => 'consumer']);
        $this->collector = User::factory()->create(['role' => 'collector']);
        $brand = Brand::create(['user_id' => $this->owner->id, 'name' => 'Circular', 'slug' => 'circular', 'is_active' => true]);
        $this->product = Product::create(['brand_id' => $brand->id, 'name' => 'Jacket', 'slug' => 'jacket', 'brand' => 'Circular', 'sku' => 'J1', 'category' => 'Jacket', 'status' => 'published', 'is_active' => true]);
        $material = Material::create(['name' => 'Polyester', 'type' => 'synthetic', 'recyclable' => true]);
        $this->product->materials()->attach($material, ['percentage' => 100]);
        $label = Label::create(['name' => 'Global Recycled Standard (GRS)', 'slug' => 'grs', 'description' => 'Test', 'is_active' => true, 'pillar' => 'environment']);
        $this->actingAs($this->owner)->post(route('brand.roadmaps.store'), ['name' => 'Circular jackets', 'label_id' => $label->id, 'products' => [$this->product->id],
            'scope_description' => 'Jacket range', 'baseline' => 'Virgin polyester', 'objective' => 'Document recycled polyester', 'starts_on' => '2026-10-01', 'target_date' => '2027-04-01'])->assertSessionHasNoErrors();
        $this->plan = LabelRoadmap::firstOrFail();
        $program = RecoveryProgram::create(['name' => 'Collecte', 'organization_name' => 'Centre', 'type' => 'recycling', 'reward_points' => 100, 'is_active' => true]);
        $this->point = CollectionPoint::create(['recovery_program_id' => $program->id, 'operator_user_id' => $this->collector->id, 'name' => 'Centre', 'address' => '1 rue', 'city' => 'Tunis', 'is_active' => true]);
    }

    private function requestData(array $extra = []): array
    {
        return array_replace(['title' => 'Polyester', 'material_name' => 'polyester', 'minimum_percentage' => 70, 'quantity_kg' => 50,
            'accepted_conditions' => ['damaged'], 'reward_points' => 50, 'roadmap_step_id' => $this->plan->steps->firstWhere('material_stage', 'collection')->id], $extra);
    }

    private function verifiedMatch(): MaterialMatch
    {
        $this->actingAs($this->owner)->post(route('brand.material-requests.store'), $this->requestData())->assertSessionHasNoErrors();
        $return = ProductReturn::create(['user_id' => $this->consumer->id, 'product_id' => $this->product->id, 'collection_point_id' => $this->point->id,
            'reference' => 'LOT-1', 'qr_token' => 'token-1', 'status' => 'completed', 'action' => 'recycling', 'condition' => 'damaged', 'confirmed_weight_kg' => 10]);

        return MaterialMatch::create(['material_request_id' => MaterialRequest::first()->id, 'product_return_id' => $return->id, 'status' => 'verified', 'match_score' => 100]);
    }

    private function assign(MaterialMatch $match): void
    {
        $this->actingAs($this->owner)->post(route('brand.material-usage.assign', $match), ['roadmap_step_id' => $this->plan->steps->firstWhere('material_stage', 'collection')->id])->assertSessionHasNoErrors()->assertRedirect();
    }

    private function advance(MaterialMatch $match, string $status, array $extra = [])
    {
        return $this->actingAs($this->owner)->post(route('brand.material-usage.update', $match), array_replace(['status' => $status, 'note' => 'Partenaire et mesures documentés.', 'proofs' => [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]], $extra));
    }

    private function submit(MaterialMatch $match, string $stage)
    {
        return $this->actingAs($this->owner)->post(route('brand.roadmaps.steps.submit', $this->plan->steps->firstWhere('material_stage', $stage)), [
            'scope_version' => 1, 'actions' => 'Actions et partenaire documentés', 'results' => 'Mesures et résultats documentés',
            'product_results' => [$this->product->id => 'Résultat pour cette référence'], 'material_match_id' => $match->id,
            'proofs' => [UploadedFile::fake()->create('review.pdf', 10, 'application/pdf')],
        ]);
    }

    public function test_complete_four_actor_journey_only_admin_approval_advances_label(): void
    {
        $this->actingAs($this->owner)->post(route('brand.material-requests.store'), $this->requestData())->assertSessionHasNoErrors();
        $return = app(ProductReturnWorkflowService::class)->create($this->consumer, ['product_id' => $this->product->id, 'collection_point_id' => $this->point->id, 'action' => 'recycling', 'condition' => 'damaged', 'weight_kg' => 10]);
        app(ProductReturnWorkflowService::class)->transition($return, 'dropoff_expected', $this->consumer);
        $this->actingAs($this->consumer)->post(route('product-returns.declare-deposit', $return), ['evidence' => UploadedFile::fake()->image('deposit.jpg')])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->collector)->patch(route('collector.returns.update', $return), ['status' => 'received', 'qr_token' => $return->qr_token, 'confirmed_weight_kg' => 10, 'evidence' => UploadedFile::fake()->image('received.jpg')])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('admin.material-reviews.update', $return), ['decision' => 'verified', 'condition' => 'damaged', 'confirmed_weight_kg' => 10])->assertSessionHasNoErrors();
        $match = MaterialMatch::firstOrFail();
        $this->actingAs($this->owner)->post(route('brand.material-matches.accept', $match))->assertSessionHasNoErrors();
        $this->actingAs($this->consumer)->post(route('consumer.material-transfers.store', $match))->assertForbidden();
        $this->actingAs($this->collector)->get(route('collector.returns.index'))->assertOk()->assertSee('Remettre la matière');
        $this->post(route('collector.material-transfers.store', $match), ['transfer_token' => $match->fresh()->transfer_token, 'note' => 'Remise au transporteur de la marque', 'evidence' => UploadedFile::fake()->image('transfer.jpg')])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->post(route('brand.material-matches.receive', $match))->assertSessionHasNoErrors();
        $this->post(route('brand.material-matches.verify', $match))->assertSessionHasNoErrors();
        $this->assertSame('validated', $return->rewardTransaction()->first()->status);
        $this->assertSame(150, $return->rewardTransaction()->first()->points);
        $this->assertSame(0, $this->plan->fresh()->progress());
        $this->assertDatabaseHas('material_usages', ['material_match_id' => $match->id, 'label_roadmap_id' => $this->plan->id, 'status' => 'stored']);
        $this->submit($match, 'collection')->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('admin.roadmaps.review', RoadmapSubmission::latest('id')->first()), ['status' => 'approved', 'review_comment' => 'Pesée et transfert vérifiés'])->assertSessionHasNoErrors();
        $this->assertSame(14, $this->plan->fresh()->progress());
        $this->advance($match, 'sent')->assertSessionHasNoErrors();
        $this->advance($match, 'processed', ['recycled_output_kg' => 8])->assertSessionHasNoErrors();
        $this->submit($match, 'transformation')->assertSessionHasNoErrors();
        $this->advance($match, 'incorporated', ['product_quantities' => [$this->product->id => 7]])->assertSessionHasNoErrors();
        $this->submit($match, 'incorporation')->assertSessionHasNoErrors();
        foreach (RoadmapSubmission::where('status', 'pending')->get() as $submission) {
            $this->actingAs($this->admin)->patch(route('admin.roadmaps.review', $submission), ['status' => 'approved', 'review_comment' => 'Bilan et documents vérifiés'])->assertSessionHasNoErrors();
        }
        $this->assertSame(43, $this->plan->fresh()->progress());
        $this->assertDatabaseCount('material_usages', 1);
        $this->assertEquals(10, $this->plan->materialUsages()->sum('input_kg'));
        $this->assertEquals(8, $this->plan->materialUsages()->sum('recycled_output_kg'));
        $this->assertEquals(7, $this->plan->materialUsages()->sum('incorporated_kg'));
        $this->get(route('admin.roadmaps.show', $this->plan))->assertOk()->assertSee('10,00 kg');
        $this->actingAs($this->owner)->get(route('brand.material-matches.index'))->assertOk()->assertSee('Incorporation documentée');
        $this->get(route('brand.roadmaps.show', $this->plan))->assertOk()->assertSee('10,00 kg');
        $this->actingAs($this->consumer)->get(route('product-returns.show', $return))->assertOk()->assertSee('Incorporation documentée');
        $event = MaterialUsage::first()->events()->first();
        $this->get(route('material-usage.proof', [$event, 0]))->assertForbidden();
        $this->actingAs($this->admin)->get(route('material-usage.proof', [$event, 0]))->assertOk();
    }

    public function test_lifecycle_order_mass_balance_and_failure_cleanup(): void
    {
        $match = $this->verifiedMatch();
        $this->assign($match);
        $this->advance($match, 'processed', ['recycled_output_kg' => 8])->assertSessionHasErrors('status');
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->advance($match, 'sent')->assertSessionHasNoErrors();
        $this->advance($match, 'processed', ['recycled_output_kg' => 11])->assertSessionHasErrors('recycled_output_kg');
        $this->advance($match, 'processed', ['recycled_output_kg' => 8])->assertSessionHasNoErrors();
        $this->advance($match, 'incorporated', ['product_quantities' => [$this->product->id => 9]])->assertSessionHasErrors('product_quantities');
        $this->advance($match, 'incorporated', ['product_quantities' => [999 => 7]])->assertSessionHasErrors('product_quantities');
        $this->advance($match, 'incorporated', ['product_quantities' => [$this->product->id => 7]])->assertSessionHasNoErrors();
        $this->advance($match, 'incorporated', ['product_quantities' => [$this->product->id => 7]])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('material_usage_events', 3);
        $this->assertCount(3, Storage::disk('local')->allFiles());
    }

    public function test_material_requires_documented_stage_and_cannot_be_claimed_by_another_plan(): void
    {
        $match = $this->verifiedMatch();
        $this->submit($match, 'transformation')->assertSessionHasErrors('material_match_id');
        $this->assign($match);
        $other = $this->plan->replicate();
        $other->name = 'Another plan';
        $other->save();
        $step = $this->plan->steps->firstWhere('material_stage', 'collection')->replicate();
        $step->label_roadmap_id = $other->id;
        $step->save();
        $this->post(route('brand.material-usage.assign', $match), ['roadmap_step_id' => $step->id])->assertSessionHasErrors('material_match_id');
        $this->assertDatabaseCount('material_usages', 1);
        $this->assertSame($this->plan->id, $match->usage->label_roadmap_id);
        $this->submit($match, 'collection')->assertSessionHasNoErrors();
        $this->submit($match, 'collection')->assertSessionHasNoErrors();
        $this->assertEquals(10, $this->plan->materialUsages()->sum('input_kg'));
    }

    public function test_foreign_brand_cannot_associate_requests_or_use_private_proofs(): void
    {
        $match = $this->verifiedMatch();
        $this->assign($match);
        $this->advance($match, 'sent')->assertSessionHasNoErrors();
        $other = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $other->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $this->actingAs($other)->post(route('brand.material-requests.store'), $this->requestData())->assertForbidden();
        $this->post(route('brand.material-usage.assign', $match), ['roadmap_step_id' => $this->plan->steps->first()->id])->assertForbidden();
        $this->post(route('brand.material-usage.update', $match), [])->assertForbidden();
        $this->get(route('material-usage.proof', [MaterialUsage::first()->events()->first(), 0]))->assertForbidden();
    }

    public function test_unconfirmed_weight_cannot_be_used_as_label_evidence(): void
    {
        $match = $this->verifiedMatch();
        $match->productReturn->update(['confirmed_weight_kg' => null, 'weight_kg' => 10]);
        $this->assign($match);
        $this->submit($match, 'collection')->assertSessionHasErrors('material_match_id');
        $this->advance($match, 'sent')->assertSessionHasErrors('status');
    }

    public function test_correction_preserves_history_and_requires_a_new_admin_review(): void
    {
        $match = $this->verifiedMatch();
        $this->assign($match);
        $this->advance($match, 'sent')->assertSessionHasNoErrors();
        $this->advance($match, 'processed', ['recycled_output_kg' => 8])->assertSessionHasNoErrors();
        $this->submit($match, 'transformation')->assertSessionHasNoErrors();
        $old = RoadmapSubmission::latest('id')->first();
        $this->actingAs($this->admin)->patch(route('admin.roadmaps.review', $old), ['status' => 'approved', 'review_comment' => 'Documents examinés'])->assertSessionHasNoErrors();
        $this->assertSame(14, $this->plan->fresh()->progress());
        $this->advance($match, 'processed', ['correction' => 1, 'recycled_output_kg' => 7])->assertSessionHasNoErrors();
        $this->assertSame(0, $this->plan->fresh()->progress());
        $this->assertSame('approved', $old->fresh()->status);
        $this->assertDatabaseCount('material_usage_events', 3);
        $this->submit($match, 'transformation')->assertSessionHasNoErrors();
        $latest = RoadmapSubmission::latest('id')->first();
        $this->assertEquals(7, $latest->material_record['recycled_output_kg']);
        $this->actingAs($this->admin)->patch(route('admin.roadmaps.review', $latest), ['status' => 'approved', 'review_comment' => 'Bilan corrigé examiné'])->assertSessionHasNoErrors();
        $this->assertSame(14, $this->plan->fresh()->progress());
    }

    public function test_reception_cannot_skip_hub_transfer_and_wrong_collector_is_blocked(): void
    {
        $match = $this->verifiedMatch();
        $match->update(['status' => 'accepted', 'transfer_token' => 'transfer-code']);
        $match->productReturn->update(['status' => 'reserved_by_brand']);
        $this->actingAs($this->owner)->post(route('brand.material-matches.receive', $match))->assertSessionHasErrors('match');
        $other = User::factory()->create(['role' => 'collector']);
        $this->actingAs($other)->post(route('collector.material-transfers.store', $match), [])->assertForbidden();
        $this->actingAs($this->collector)->post(route('collector.material-transfers.store', $match), ['transfer_token' => 'wrong', 'note' => 'Remise contrôlée', 'evidence' => UploadedFile::fake()->image('handover.jpg')])->assertSessionHasErrors('transfer_token');
        $this->assertSame('accepted', $match->fresh()->status);
    }

    public function test_direct_consumer_transfer_is_kept_separate_from_hub_handover(): void
    {
        $match = $this->verifiedMatch();
        $match->update(['status' => 'accepted', 'transfer_token' => 'direct-code']);
        $match->productReturn->update(['status' => 'reserved_by_brand', 'logistics_mode' => 'consumer_transfer']);
        $this->actingAs($this->collector)->post(route('collector.material-transfers.store', $match), [])->assertForbidden();
        $this->actingAs($this->consumer)->post(route('consumer.material-transfers.store', $match))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('in_transit', $match->fresh()->status);
        $this->actingAs($this->owner)->post(route('brand.material-matches.receive', $match))->assertSessionHasNoErrors();
    }

    public function test_request_capacity_accounts_for_other_reserved_returns(): void
    {
        $match = $this->verifiedMatch();
        $match->request->update(['quantity_kg' => 12, 'remaining_kg' => 12]);
        $match->productReturn->update(['status' => 'marketplace', 'admin_review_status' => 'verified']);
        $match->update(['status' => 'suggested']);
        $this->actingAs($this->owner)->post(route('brand.material-matches.accept', $match))->assertSessionHasNoErrors();
        $secondReturn = $match->productReturn->replicate();
        $secondReturn->reference = 'LOT-2';
        $secondReturn->qr_token = 'token-2';
        $secondReturn->confirmed_weight_kg = 5;
        $secondReturn->status = 'marketplace';
        $secondReturn->save();
        $second = MaterialMatch::create(['material_request_id' => $match->material_request_id, 'product_return_id' => $secondReturn->id, 'match_score' => 100, 'status' => 'suggested']);
        $this->post(route('brand.material-matches.accept', $second))->assertSessionHasErrors('match');
        $this->assertSame('suggested', $second->fresh()->status);
    }

    public function test_allocated_scope_is_preserved_and_archival_blocks_usage_updates(): void
    {
        $match = $this->verifiedMatch();
        $this->assign($match);
        $this->put(route('brand.roadmaps.update', $this->plan), ['name' => $this->plan->name, 'products' => [$this->product->id],
            'scope_description' => $this->plan->scope_description, 'baseline' => $this->plan->baseline, 'objective' => 'Changed product scope',
            'starts_on' => '2026-10-01', 'target_date' => '2027-04-01'])->assertSessionHasErrors('products');
        $this->delete(route('brand.roadmaps.destroy', $this->plan))->assertRedirect();
        $this->advance($match, 'sent')->assertSessionHasErrors('material_match_id');
        $this->assertDatabaseCount('material_usage_events', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->post(route('brand.roadmaps.restore', $this->plan))->assertRedirect();
        $this->advance($match, 'sent')->assertSessionHasNoErrors();
    }

    public function test_duplicate_verification_and_late_rejection_preserve_reward_and_stock(): void
    {
        $match = $this->verifiedMatch();
        $match->update(['status' => 'brand_received']);
        $exchange = app(MaterialExchangeService::class);
        $stale = $match->fresh();
        $exchange->verify($match);
        $remaining = $match->request->fresh()->remaining_kg;
        $this->actingAs($this->owner)->post(route('brand.material-matches.verify', $stale))->assertSessionHasErrors('match');
        $this->post(route('brand.material-matches.reject', $match), ['note' => 'Late rejection'])->assertSessionHasErrors('match');
        $this->assertSame($remaining, $match->request->fresh()->remaining_kg);
        $this->assertSame('verified', $match->fresh()->status);
        $this->assertDatabaseCount('material_usages', 1);
    }
}
