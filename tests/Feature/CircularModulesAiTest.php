<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\ProductImpactReport;
use App\Models\RepairOrder;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\Ai\ImpactAdvisorService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fonctionnalités IA des modules (Gemini simulé avec Http::fake, et repli local sans clé).
 */
class CircularModulesAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function fakeGemini(array $answer): void
    {
        config(['services.gemini.enabled' => true, 'services.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['output_text' => json_encode($answer)])]);
    }

    private function withoutGemini(): void
    {
        config(['services.gemini.enabled' => false, 'services.gemini.key' => null]);
        Http::fake();
    }

    public function test_certificate_photo_is_read_by_gemini_and_prefills_the_form(): void
    {
        $this->fakeGemini([
            'type' => 'GRS', 'certificate_number' => 'CU-GRS-778899', 'issuer' => 'Control Union', 'issued_at' => '2026-01-10',
            'expires_at' => '2027-01-09', 'material' => 'Recycled polyester', 'holder' => 'Filature Méditerranée', 'confidence' => 92,
        ]);
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $batch = SupplierBatch::where('batch_code', 'LOT-FM-2026-01')->firstOrFail();

        $this->actingAs($brandUser)->post(route('brand.certificates.ai-scan', $batch), ['photo' => UploadedFile::fake()->image('certificat.jpg')])
            ->assertSessionHas('ai_certificate.fields.certificate_number', 'CU-GRS-778899');

        $this->get(route('brand.suppliers.show', $batch->supplier))->assertSee('CU-GRS-778899')->assertSee('IA Gemini');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage') && $request['input'][1]['type'] === 'image');
    }

    public function test_supplier_risk_analysis_detects_expired_certificate_without_gemini(): void
    {
        $this->withoutGemini();
        $batch = SupplierBatch::where('batch_code', 'LOT-FM-2026-01')->firstOrFail();
        MaterialOriginCertificate::factory()->for($batch, 'batch')->create(['type' => 'GRS', 'status' => 'verified', 'expires_at' => now()->subDay()]);
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.suppliers.ai-risk', $batch->supplier))->assertSessionHasNoErrors();

        $risk = $batch->supplier->fresh()->ai_risk;
        $this->assertSame('local', $risk['source']);
        $this->assertTrue(collect($risk['risks'])->contains(fn ($item) => str_contains($item['text'], 'expiré')));
        $this->get(route('admin.suppliers.show', $batch->supplier))->assertSee('Analyse locale');
    }

    public function test_impact_recommendations_simulate_a_better_score(): void
    {
        $this->withoutGemini();
        $report = ProductImpactReport::whereHas('product', fn ($q) => $q->where('name', 'Heritage Wool Coat'))->firstOrFail();
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();

        $this->actingAs($brandUser)->post(route('brand.impact.ai-recommendations', $report))->assertSessionHasNoErrors();

        $ai = $report->fresh()->ai_recommendations;
        $this->assertGreaterThan($ai['current_score'], $ai['best_score']);
        $this->assertStringContainsString('Laine recyclée', $ai['recommendations'][0]['title']);
        $this->get(route('brand.impact.show', $report))->assertSee('Comment améliorer ce score');
    }

    public function test_impact_recommendations_use_gemini_explanations_but_keep_computed_scores(): void
    {
        $report = ProductImpactReport::whereHas('product', fn ($q) => $q->where('name', 'Heritage Wool Coat'))->firstOrFail();
        $this->fakeGemini(['summary' => 'La laine pèse lourd : passez au recyclé.', 'explanations' => [['id' => 'swap-'.Material::where('name', 'Laine mérinos')->value('id'), 'explanation' => 'Demandez du RWS recyclé à votre filature.']]]);

        $ai = app(ImpactAdvisorService::class)->recommend($report);

        $this->assertSame('gemini', $ai['source']);
        $this->assertSame('La laine pèse lourd : passez au recyclé.', $ai['summary']);
        $this->assertSame('Demandez du RWS recyclé à votre filature.', $ai['recommendations'][0]['detail']);
        $this->assertGreaterThan($ai['current_score'], $ai['recommendations'][0]['projected_score']);
    }

    public function test_repair_photo_diagnosis_prefills_decision_and_resale_price(): void
    {
        $this->fakeGemini(['decision' => 'resale', 'grade' => 'B', 'damages' => ['Semelle usée'], 'estimated_cost' => 25, 'resale_price' => 95, 'explanation' => 'Semelle à remplacer, cuir en bon état.', 'confidence' => 81]);
        $admin = User::where('role', 'admin')->firstOrFail();
        $order = RepairOrder::where('status', 'requested')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.repair-orders.ai-diagnosis', $order), ['photo' => UploadedFile::fake()->image('article.jpg')])->assertSessionHasNoErrors();

        $this->assertSame('gemini-vision', $order->fresh()->ai_diagnosis['source']);
        $this->get(route('admin.repair-orders.show', $order))->assertSee('Semelle usée')->assertSee('IA Gemini Vision');
    }

    public function test_consumer_repair_request_gets_local_pre_diagnosis(): void
    {
        $this->withoutGemini();
        $consumer = User::where('email', 'consumer@textilecycle.test')->firstOrFail();
        $return = $consumer->productReturns()->whereDoesntHave('repairOrders')->firstOrFail();

        $this->actingAs($consumer)->post(route('consumer.repairs.store'), [
            'product_return_id' => $return->id,
            'issue_description' => 'La fermeture est cassée et il y a une petite tache sur la manche.',
        ])->assertSessionHasNoErrors();

        $diagnosis = RepairOrder::latest('id')->first()->ai_diagnosis;
        $this->assertSame('local', $diagnosis['source']);
        $this->assertContains('Fermeture éclair défectueuse', $diagnosis['damages']);
        $this->assertSame('resale', $diagnosis['decision']);
    }
}
