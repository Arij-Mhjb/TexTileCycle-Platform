<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandApplication;
use App\Models\CollectionPoint;
use App\Models\Label;
use App\Models\LabelRoadmap;
use App\Models\MaterialMatch;
use App\Models\MaterialRequest;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\RoadmapSubmission;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductLabelRoadmapTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Product $product;

    private Label $label;

    protected function setUp(): void
    {
        parent::setUp();
        config(['roadmaps.proof_storage' => 'local']);
        Storage::fake('local');
        $this->owner = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $this->owner->id, 'name' => 'Atelier', 'slug' => 'atelier', 'is_active' => true]);
        $this->product = Product::create(['brand_id' => $brand->id, 'brand' => 'Atelier', 'name' => 'Jacket', 'slug' => 'jacket', 'sku' => 'J1', 'category' => 'Jacket']);
        $this->label = Label::create(['name' => 'Global Recycled Standard (GRS)', 'slug' => 'grs-test', 'description' => 'Test', 'is_active' => true, 'pillar' => 'environment']);
    }

    private function data(array $extra = []): array
    {
        return array_replace(['name' => 'Jackets recyclées', 'label_id' => $this->label->id, 'products' => [$this->product->id],
            'scope_description' => 'Collection vestes polyester', 'baseline' => 'Polyester vierge actuellement', 'objective' => 'Migrer vers un polyester recyclé documenté',
            'starts_on' => '2026-10-07', 'target_date' => '2027-04-07'], $extra);
    }

    private function plan(): LabelRoadmap
    {
        $this->actingAs($this->owner)->post(route('brand.roadmaps.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect();

        return LabelRoadmap::firstOrFail();
    }

    private function submit(LabelRoadmap $plan, array $extra = []): void
    {
        $this->actingAs($this->owner)->post(route('brand.roadmaps.steps.submit', $plan->steps->first()), array_replace([
            'scope_version' => $plan->scope_version, 'actions' => 'Contrôle de tous les fournisseurs', 'results' => 'Composition documentée pour les vestes',
            'product_results' => [$this->product->id => 'Polyester recyclé identifié'],
            'proofs' => [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]], $extra))->assertSessionHasNoErrors()->assertRedirect();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_product_scope_creates_dated_steps_and_renders_both_interfaces(): void
    {
        $plan = $this->plan();
        $this->assertCount(7, $plan->steps);
        $this->assertSame(0, $plan->progress());
        $this->assertSame('Jacket', json_decode($plan->products->first()->pivot->snapshot, true)['name']);
        foreach ($plan->steps as $step) {
            $this->assertTrue($step->due_date->between($plan->starts_on, $plan->target_date));
        }
        $this->get(route('brand.roadmaps.index'))->assertOk();
        $this->get(route('brand.roadmaps.show', $plan))->assertOk()->assertSee('Jacket');
        $this->get(route('brand.roadmaps.edit', $plan))->assertOk();
        $this->get(route('brand.labels.show', $this->label))->assertOk()->assertSee('Créer une feuille de route');
        $this->actingAs($this->admin())->get(route('admin.roadmaps.show', $plan))->assertOk();
    }

    public function test_foreign_products_and_empty_scope_are_rejected(): void
    {
        $other = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $other->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $product = Product::create(['brand_id' => $brand->id, 'brand' => 'Other', 'name' => 'Other', 'slug' => 'other', 'sku' => 'O1', 'category' => 'Other']);
        $this->actingAs($this->owner)->post(route('brand.roadmaps.store'), $this->data(['products' => [$product->id]]))->assertSessionHasErrors('products.0');
        $this->post(route('brand.roadmaps.store'), $this->data(['products' => []]))->assertSessionHasErrors('products');
        $this->assertDatabaseCount('label_roadmaps', 0);
    }

    public function test_submission_requires_per_product_results_and_private_proofs(): void
    {
        $plan = $this->plan();
        $this->post(route('brand.roadmaps.steps.submit', $plan->steps->first()), ['scope_version' => 1, 'actions' => 'Detailed action performed', 'results' => 'Detailed results obtained', 'product_results' => []])->assertSessionHasErrors(['proofs', 'product_results.'.$this->product->id]);
        $this->submit($plan);
        $submission = RoadmapSubmission::firstOrFail();
        $this->assertSame('pending', $submission->status);
        $this->assertSame('submitted', $plan->steps->first()->fresh()->status);
        $this->actingAs($this->admin())->get(route('admin.roadmaps.index'))->assertOk()
            ->assertSee('Soumissions à vérifier')
            ->assertSee('Atelier')
            ->assertSee('soumission v1');
        Storage::disk('local')->assertExists($submission->files[0]['path']);
        $this->get(route('roadmaps.proofs.download', [$submission, 0]))->assertOk()->assertDownload();
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get(route('roadmaps.proofs.download', [$submission, 0]))->assertForbidden();
    }

    public function test_only_admin_can_review_and_latest_submission_is_enforced(): void
    {
        $plan = $this->plan();
        $this->submit($plan);
        $old = RoadmapSubmission::firstOrFail();
        $this->patch(route('admin.roadmaps.review', $old), ['status' => 'approved', 'review_comment' => 'Documents vérifiés'])->assertForbidden();
        $this->submit($plan);
        $latest = RoadmapSubmission::latest('id')->first();
        $this->actingAs($this->admin())->patch(route('admin.roadmaps.review', $old), ['status' => 'approved', 'review_comment' => 'Documents vérifiés'])->assertConflict();
        $this->patch(route('admin.roadmaps.review', $latest), ['status' => 'approved', 'review_comment' => 'Documents vérifiés'])->assertRedirect();
        $this->assertSame('approved', $latest->fresh()->status);
        $this->assertSame(14, $plan->fresh()->progress());
        $this->patch(route('admin.roadmaps.review', $latest), ['status' => 'approved', 'review_comment' => 'Second examen'])->assertConflict();
    }

    public function test_scope_changes_reset_approvals_and_keep_old_evidence(): void
    {
        $plan = $this->plan();
        $this->submit($plan);
        $submission = RoadmapSubmission::firstOrFail();
        $this->actingAs($this->admin())->patch(route('admin.roadmaps.review', $submission), ['status' => 'approved', 'review_comment' => 'Documents vérifiés'])->assertRedirect();
        $this->actingAs($this->owner)->put(route('brand.roadmaps.update', $plan), $this->data(['objective' => 'Nouvel objectif pour la prochaine collection']))->assertSessionHasNoErrors()->assertRedirect();
        $plan->refresh();
        $this->assertSame(2, $plan->scope_version);
        $this->assertSame(0, $plan->progress());
        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertSame(1, $submission->scope_version);
        Storage::disk('local')->assertExists($submission->files[0]['path']);
        $this->post(route('brand.roadmaps.steps.submit', $plan->steps->first()), ['scope_version' => 1])->assertSessionHasErrors('scope_version');
    }

    public function test_archival_blocks_new_submissions_and_restore_keeps_history(): void
    {
        $plan = $this->plan();
        $this->submit($plan);
        $this->delete(route('brand.roadmaps.destroy', $plan))->assertRedirect();
        $this->post(route('brand.roadmaps.steps.submit', $plan->steps->first()), [])->assertConflict();
        $this->post(route('brand.roadmaps.restore', $plan))->assertRedirect();
        $this->assertSame('active', $plan->fresh()->status);
        $this->assertDatabaseCount('roadmap_submissions', 1);
    }

    public function test_other_brand_cannot_access_scope_or_proof(): void
    {
        $plan = $this->plan();
        $this->submit($plan);
        $submission = RoadmapSubmission::first();
        $other = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $other->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $this->actingAs($other)->get(route('brand.roadmaps.show', $plan))->assertForbidden();
        $this->get(route('roadmaps.proofs.download', [$submission, 0]))->assertForbidden();
        $this->put(route('brand.roadmaps.update', $plan), $this->data())->assertForbidden();
    }

    public function test_verified_collection_records_input_output_and_incorporation_separately(): void
    {
        $plan = $this->plan();
        $program = RecoveryProgram::create(['name' => 'Collecte', 'organization_name' => 'Centre', 'type' => 'recycling', 'reward_points' => 0, 'is_active' => true]);
        $point = CollectionPoint::create(['recovery_program_id' => $program->id, 'name' => 'Centre', 'address' => '1 rue', 'city' => 'Tunis', 'is_active' => true]);
        $return = ProductReturn::create(['user_id' => $this->owner->id, 'product_id' => $this->product->id, 'collection_point_id' => $point->id, 'reference' => 'LOT-1', 'qr_token' => 'lot-token', 'status' => 'completed', 'action' => 'recycling', 'condition' => 'damaged', 'weight_kg' => 10, 'confirmed_weight_kg' => 9]);
        $request = MaterialRequest::create(['brand_id' => $this->owner->brand->id, 'title' => 'Polyester', 'material_name' => 'polyester', 'minimum_percentage' => 70, 'quantity_kg' => 10, 'remaining_kg' => 0, 'status' => 'active']);
        $match = MaterialMatch::create(['material_request_id' => $request->id, 'product_return_id' => $return->id, 'match_score' => 90, 'status' => 'verified']);
        $payload = ['scope_version' => 1, 'actions' => 'Recyclage avec un partenaire', 'results' => 'Matière incorporée aux prototypes', 'product_results' => [$this->product->id => 'Prototype avec matière recyclée'], 'material_match_id' => $match->id, 'material_name' => 'Polyester', 'recycled_output_kg' => 10, 'incorporated_kg' => 7, 'proofs' => [UploadedFile::fake()->create('lot.pdf', 10, 'application/pdf')]];
        $this->post(route('brand.roadmaps.steps.submit', $plan->steps->first()), $payload)->assertSessionHasErrors('material_match_id');
        $this->assertDatabaseCount('roadmap_submissions', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $payload['recycled_output_kg'] = null;
        $payload['incorporated_kg'] = null;
        $this->post(route('brand.roadmaps.steps.submit', $plan->steps->firstWhere('material_stage', 'collection')), $payload)->assertSessionHasNoErrors();
        $submission = RoadmapSubmission::firstOrFail();
        $this->assertSame('9.00', $submission->material_record['verified_collected_kg']);
        $this->assertNull($submission->material_record['recycled_output_kg']);
        $this->assertNull($submission->material_record['incorporated_kg']);
        $this->assertSame(0, $plan->fresh()->progress());
        $this->get(route('brand.roadmaps.show', $plan))->assertOk()->assertSee('LOT-1');
    }

    public function test_group_requires_results_for_every_product_and_new_brands_use_approved_labels(): void
    {
        $this->actingAs($this->owner);
        $second = Product::create(['brand_id' => $this->owner->brand->id, 'brand' => 'Atelier', 'name' => 'Jacket 2', 'slug' => 'jacket-2', 'sku' => 'J2', 'category' => 'Jacket']);
        $this->post(route('brand.roadmaps.store'), $this->data(['products' => [$this->product->id, $second->id]]))->assertRedirect();
        $plan = LabelRoadmap::firstOrFail();
        $this->post(route('brand.roadmaps.steps.submit', $plan->steps->first()), ['scope_version' => 1, 'actions' => 'Detailed action performed', 'results' => 'Detailed results obtained', 'product_results' => [$this->product->id => 'Documented result'], 'proofs' => [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]])->assertSessionHasErrors('product_results.'.$second->id);
        BrandApplication::create(['brand_id' => $this->owner->brand->id, 'answers' => [], 'status' => 'approved', 'analysis_status' => 'completed']);
        $this->post(route('brand.roadmaps.store'), $this->data())->assertSessionHasErrors('label_id');
        $this->owner->brand->approvedLabels()->attach($this->label);
        $this->post(route('brand.roadmaps.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_corrections_require_new_proofs_and_brand_cannot_self_approve(): void
    {
        $plan = $this->plan();
        $this->submit($plan);
        $submission = RoadmapSubmission::firstOrFail();
        $this->actingAs($this->admin())->patch(route('admin.roadmaps.review', $submission), [
            'status' => 'rejected', 'review_comment' => 'Ajouter les fiches fournisseurs.',
        ])->assertRedirect();
        $this->assertSame('changes_requested', $plan->steps->first()->fresh()->status);
        $this->actingAs($this->owner)->patch(route('brand.roadmaps.steps.update', $plan->steps->first()), [
            'goal' => 'Contrôler les fiches fournisseurs', 'owner_name' => 'Atelier',
            'due_date' => '2026-11-01', 'status' => 'approved',
        ])->assertSessionHasErrors('status');
        $this->submit($plan);
        $this->assertDatabaseCount('roadmap_submissions', 2);
        $this->assertSame('rejected', $submission->fresh()->status);
        $this->assertSame(2, RoadmapSubmission::latest('id')->first()->version);
        $this->assertSame(0, $plan->fresh()->progress());
    }

    public function test_cloud_proof_download_uses_signed_authenticated_raw_delivery(): void
    {
        config(['services.cloudinary.url' => 'cloudinary://test-key:test-secret@test-cloud']);
        Http::fake(['res.cloudinary.com/*' => Http::response('private-document', 200)]);
        $service = new MediaStorageService;
        $content = $service->readRoadmapProof([
            'storage' => 'cloudinary', 'path' => 'textilecycle/roadmaps/1/proof.pdf',
            'version' => 12345, 'name' => 'proof.pdf',
        ]);
        $this->assertSame('private-document', $content);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/raw/authenticated/s--')
            && str_contains($request->url(), '/v12345/textilecycle/roadmaps/1/proof.pdf')
        );
    }

    public function test_shortened_timeline_keeps_all_deadlines_within_scope(): void
    {
        $plan = $this->plan();
        $this->put(route('brand.roadmaps.update', $plan), $this->data(['target_date' => '2026-10-07']))->assertSessionHasNoErrors();
        foreach ($plan->fresh()->steps as $step) {
            $this->assertSame('2026-10-07', $step->due_date->format('Y-m-d'));
        }
    }
}
