<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\ProductImpactReport;
use App\Models\RepairOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Notifications\SupplierPurchaseOrderMail;
use App\Services\Ai\ImpactAdvisorService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_brand_only_uploads_the_certificate_photo_and_ai_fills_everything(): void
    {
        $this->fakeGemini([
            'type' => 'GRS', 'certificate_number' => 'CU-GRS-778899', 'issuer' => 'Control Union', 'issued_at' => '2026-01-10',
            'expires_at' => '2027-01-09', 'material' => 'Recycled polyester', 'holder' => 'Filature Méditerranée', 'confidence' => 92,
        ]);
        Storage::fake('local');
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $batch = SupplierBatch::where('batch_code', 'LOT-FM-2026-01')->firstOrFail();

        $this->actingAs($brandUser)->post(route('brand.certificates.store', $batch), ['document' => UploadedFile::fake()->image('certificat.jpg')])
            ->assertSessionHasNoErrors();

        $certificate = MaterialOriginCertificate::where('certificate_number', 'CU-GRS-778899')->firstOrFail();
        $this->assertSame('GRS', $certificate->type);
        $this->assertSame('2027-01-09', $certificate->expires_at->toDateString());
        $this->assertSame('pending', $certificate->status);
        Storage::disk('local')->assertExists($certificate->document_path);
        $this->get(route('brand.suppliers.show', $batch->supplier))->assertSee('CU-GRS-778899')->assertSee('IA Gemini');
        $this->get(route('brand.certificates.document', $certificate))->assertOk();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage') && $request['input'][1]['type'] === 'image');
    }

    public function test_image_that_is_not_a_certificate_is_rejected_by_ai(): void
    {
        $this->fakeGemini(['is_certificate' => false, 'document_kind' => 'diagramme UML', 'type' => null, 'certificate_number' => null, 'issuer' => null, 'issued_at' => null, 'expires_at' => null, 'material' => null, 'holder' => null, 'confidence' => 95]);
        Storage::fake('local');
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $batch = SupplierBatch::where('batch_code', 'LOT-LN-2026-03')->firstOrFail();

        $this->actingAs($brandUser)->post(route('brand.certificates.store', $batch), ['document' => UploadedFile::fake()->image('diagramme.png')])
            ->assertSessionHasErrors('document');

        $this->assertSame(0, $batch->certificates()->count());
    }

    public function test_unreadable_certificate_is_still_saved_for_admin_review(): void
    {
        $this->withoutGemini();
        Storage::fake('local');
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $batch = SupplierBatch::where('batch_code', 'LOT-LN-2026-03')->firstOrFail();

        $this->actingAs($brandUser)->post(route('brand.certificates.store', $batch), ['document' => UploadedFile::fake()->image('flou.jpg', 20, 20)])
            ->assertSessionHasNoErrors();

        $certificate = $batch->certificates()->latest('id')->firstOrFail();
        $this->assertFalse($certificate->isComplete());
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.suppliers.index'))->assertSee('Certificat (type non lu)')->assertSee('Voir le document');
    }

    public function test_supplier_receives_signed_link_and_declares_shipment_with_certificate(): void
    {
        Notification::fake();
        Storage::fake('local');
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-TEST-1', 'status' => 'ordered', 'ordered_at' => now()]);

        $this->actingAs($brandUser)->post(route('brand.purchase-orders.send', $order))->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(SupplierPurchaseOrderMail::class);
        $this->assertSame('sent', $order->fresh()->status);

        // Sans signature : refusé. Avec le lien signé : accessible sans compte.
        auth()->logout();
        $this->get('/fournisseur/commandes/'.$order->id)->assertForbidden();
        $this->get($order->supplierPortalUrl())->assertOk()->assertSee('PO-TEST-1')->assertSee('Accepter la commande')->assertDontSee('Confirmer l’expédition');

        // Le fournisseur accepte la commande depuis la page ouverte par le bouton de l'e-mail.
        $answerUrl = URL::temporarySignedRoute('supplier-portal.answer', now()->addHour(), ['purchaseOrder' => $order->id]);
        $this->post($answerUrl, ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $order->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $brandUser->id, 'type' => 'supplier_order_accepted']);
        $this->get($order->supplierPortalUrl())->assertSee('Confirmer l’expédition');

        $this->fakeGemini(['is_certificate' => true, 'document_kind' => 'certificat GRS', 'type' => 'GRS', 'certificate_number' => 'CU-GRS-5555', 'issuer' => 'Control Union',
            'issued_at' => '2026-03-01', 'expires_at' => '2027-03-01', 'material' => 'Recycled polyester', 'holder' => 'Filature Méditerranée', 'confidence' => 90]);
        $shipUrl = URL::temporarySignedRoute('supplier-portal.ship', now()->addHour(), ['purchaseOrder' => $order->id]);
        $this->post($shipUrl, [
            'material_id' => Material::where('name', 'Polyester recyclé')->value('id'),
            'quantity_kg' => 250, 'origin_country' => 'Tunisie', 'certificate' => UploadedFile::fake()->image('grs.jpg'),
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $batch = $order->batches()->with('certificates')->firstOrFail();
        $this->assertSame('CU-GRS-5555', $batch->certificates->first()->certificate_number);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $brandUser->id, 'type' => 'supplier_shipment']);
    }

    public function test_supplier_portal_rejects_a_photo_that_is_not_a_certificate(): void
    {
        Storage::fake('local');
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-TEST-2', 'status' => 'accepted', 'ordered_at' => now()]);
        $this->fakeGemini(['is_certificate' => false, 'document_kind' => 'selfie', 'type' => null, 'certificate_number' => null, 'issuer' => null, 'issued_at' => null, 'expires_at' => null, 'material' => null, 'holder' => null, 'confidence' => 99]);

        $shipUrl = URL::temporarySignedRoute('supplier-portal.ship', now()->addHour(), ['purchaseOrder' => $order->id]);
        $this->post($shipUrl, ['material_id' => Material::value('id'), 'quantity_kg' => 10, 'origin_country' => 'Tunisie', 'certificate' => UploadedFile::fake()->image('selfie.jpg')])
            ->assertSessionHasErrors('certificate');

        $this->assertSame(0, $order->batches()->count());
        $this->assertSame('accepted', $order->fresh()->status);
    }

    public function test_supplier_can_decline_with_a_reason_and_cannot_ship(): void
    {
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-TEST-3', 'status' => 'sent', 'ordered_at' => now()]);
        $answerUrl = URL::temporarySignedRoute('supplier-portal.answer', now()->addHour(), ['purchaseOrder' => $order->id]);

        $this->post($answerUrl, ['decision' => 'decline'])->assertSessionHasErrors('decline_reason');
        $this->post($answerUrl, ['decision' => 'decline', 'decline_reason' => 'Stock épuisé jusqu’en décembre.'])->assertSessionHasNoErrors();

        $this->assertSame('declined', $order->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['type' => 'supplier_order_declined']);
        $shipUrl = URL::temporarySignedRoute('supplier-portal.ship', now()->addHour(), ['purchaseOrder' => $order->id]);
        $this->post($shipUrl, [])->assertStatus(410);
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
