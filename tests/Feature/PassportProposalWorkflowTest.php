<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CollectionPoint;
use App\Models\PassportProposal;
use App\Models\Product;
use App\Models\RecoveryProgram;
use App\Models\Scan;
use App\Models\User;
use App\Services\PassportProposalWorkflowService;
use App\Services\ProductReturnWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PassportProposalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_without_known_brand_publishes_a_traceable_passport(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $scan = $this->scan($consumer, ['brand' => 'Marque inconnue']);
        $workflow = app(PassportProposalWorkflowService::class);
        $point = $this->point();

        $proposal = $workflow->createFromScan($scan, $consumer);
        $proposal = $workflow->adminReview($proposal, $admin, 'approve', ['note' => 'Données cohérentes', 'collection_point_ids' => [$point->id]]);

        $this->assertTrue($proposal->isReady());
        $this->assertSame('published', $proposal->product->status);
        $this->assertSame('Tunisia', $proposal->product->origin);
        $this->assertTrue($proposal->collectionPoints->contains($point));
        $this->assertDatabaseHas('passport_events', ['passport_proposal_id' => $proposal->id, 'event' => 'admin_approve']);
    }

    public function test_brand_confirmation_is_required_when_brand_is_identified(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $brandUser = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $brandUser->id, 'name' => 'Eco Brand', 'slug' => 'eco-brand', 'is_active' => true]);
        $workflow = app(PassportProposalWorkflowService::class);
        $proposal = $workflow->createFromScan($this->scan($consumer, ['brand' => 'Eco Brand']), $consumer);
        $point = $this->point();

        $proposal = $workflow->adminReview($proposal, $admin, 'approve', ['brand_id' => $brand->id, 'collection_point_ids' => [$point->id]]);
        $this->assertSame('brand_pending', $proposal->status);
        $this->assertSame('draft', $proposal->product->status);

        $proposal = $workflow->brandReview($proposal, $brandUser->fresh(), 'correct', [
            'manufacturing_country' => 'France',
            'note' => 'Pays corrigé depuis nos données produit.',
        ]);
        $this->assertTrue($proposal->isReady());
        $this->assertSame('published', $proposal->product->fresh()->status);
        $this->assertSame('France', $proposal->product->fresh()->origin);
    }

    public function test_consumer_can_answer_an_information_request(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $workflow = app(PassportProposalWorkflowService::class);
        $proposal = $workflow->createFromScan($this->scan($consumer), $consumer);
        $proposal = $workflow->adminReview($proposal, $admin, 'request_information', ['note' => 'Pays à confirmer']);

        $proposal = $workflow->consumerSupplement($proposal, $consumer, ['manufacturing_country' => 'Portugal']);

        $this->assertSame('admin_pending', $proposal->status);
        $this->assertSame('Portugal', $proposal->proposed_data['manufacturing_country']);
        $this->assertDatabaseHas('passport_events', ['passport_proposal_id' => $proposal->id, 'event' => 'consumer_supplemented']);
    }

    public function test_multimodal_return_is_blocked_until_passport_is_ready(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $scan = $this->scan($consumer);
        $workflow = app(PassportProposalWorkflowService::class);
        $proposal = $workflow->createFromScan($scan, $consumer);
        $product = Product::create([
            'name' => 'Brouillon', 'slug' => 'brouillon', 'brand' => 'Test', 'sku' => 'BLOCK-1',
            'category' => 'Textile', 'status' => 'published', 'is_active' => true,
        ]);
        $proposal->update(['product_id' => $product->id]);
        $scan->update(['product_id' => $product->id]);

        $this->expectException(ValidationException::class);
        app(ProductReturnWorkflowService::class)->create($consumer, [
            'scan_id' => $scan->id,
            'product_id' => $product->id,
            'collection_point_id' => $this->point()->id,
            'action' => 'recycling',
        ]);
    }

    public function test_ready_passport_accepts_only_the_partner_point_selected_by_admin(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $approvedPoint = $this->point('recycling', 'autorise');
        $otherPoint = $this->point('recycling', 'non-autorise');
        $passportWorkflow = app(PassportProposalWorkflowService::class);
        $scan = $this->scan($consumer);
        $proposal = $passportWorkflow->createFromScan($scan, $consumer);
        $proposal = $passportWorkflow->adminReview($proposal, $admin, 'approve', [
            'collection_point_ids' => [$approvedPoint->id],
        ]);

        $this->expectException(ValidationException::class);
        app(ProductReturnWorkflowService::class)->create($consumer, [
            'scan_id' => $scan->id,
            'product_id' => $proposal->product_id,
            'collection_point_id' => $otherPoint->id,
            'action' => 'recycling',
        ]);
    }

    private function scan(User $consumer, array $overrides = []): Scan
    {
        return Scan::create([
            'user_id' => $consumer->id,
            'source_type' => 'multimodal',
            'status' => 'consumer_confirmed',
            'ai_status' => 'completed',
            'consumer_confirmed_at' => now(),
            'extracted_data' => array_replace([
                'brand' => null,
                'product_name' => 'Chemise proposée',
                'sku' => 'SCAN-PROP-1',
                'category' => 'Chemise',
                'manufacturing_country' => 'Tunisia',
                'condition' => 'good',
                'composition' => [['material' => 'Coton', 'percentage' => 100]],
            ], $overrides),
        ]);
    }

    private function point(string $type = 'recycling', string $suffix = ''): CollectionPoint
    {
        $program = RecoveryProgram::create([
            'name' => 'Programme '.$type.' '.$suffix, 'organization_name' => 'Centre '.$suffix, 'type' => $type,
            'reward_points' => 50, 'is_active' => true,
        ]);

        return CollectionPoint::create([
            'recovery_program_id' => $program->id, 'name' => 'Point test '.$suffix,
            'address' => '1 rue test', 'city' => 'Tunis', 'is_active' => true,
        ]);
    }
}
