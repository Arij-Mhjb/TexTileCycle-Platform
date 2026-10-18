<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\ProductImpactReport;
use App\Models\RepairOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\Ai\ImpactAdvisorService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    /** Fournisseur « Filature Méditerranée » avec un compte actif (mot de passe déjà choisi). */
    private function supplierAccount(): array
    {
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $account = User::factory()->create(['role' => 'supplier', 'email' => 'compte@filature.tn']);
        $supplier->update(['user_id' => $account->id]);

        return [$supplier, $account];
    }

    public function test_supplier_accepts_from_his_space_and_declares_shipment_with_certificate(): void
    {
        Storage::fake('local');
        [$supplier, $account] = $this->supplierAccount();
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-TEST-1', 'status' => 'ordered', 'ordered_at' => now()]);

        // La marque ne peut pas répondre à la place du fournisseur.
        $this->actingAs($brandUser)->post(route('supplier.orders.answer', $order), ['decision' => 'accept'])->assertForbidden();

        $this->actingAs($account)->get(route('supplier.orders.show', $order))->assertOk()->assertSee('Accepter la commande')->assertDontSee('Confirmer l’expédition');
        $this->post(route('supplier.orders.answer', $order), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $order->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $brandUser->id, 'type' => 'supplier_order_accepted']);

        $this->fakeGemini(['is_certificate' => true, 'document_kind' => 'certificat GRS', 'type' => 'GRS', 'certificate_number' => 'CU-GRS-5555', 'issuer' => 'Control Union',
            'issued_at' => '2026-03-01', 'expires_at' => '2027-03-01', 'material' => 'Recycled polyester', 'holder' => 'Filature Méditerranée', 'confidence' => 90]);
        $this->post(route('supplier.orders.ship', $order), [
            'material_id' => Material::where('name', 'Polyester recyclé')->value('id'),
            'quantity_kg' => 250, 'origin_country' => 'Tunisie', 'certificate' => UploadedFile::fake()->image('grs.jpg'),
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('CU-GRS-5555', $order->batches()->firstOrFail()->certificates()->firstOrFail()->certificate_number);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $brandUser->id, 'type' => 'supplier_shipment']);
    }

    public function test_supplier_shipment_rejects_a_photo_that_is_not_a_certificate(): void
    {
        Storage::fake('local');
        [$supplier, $account] = $this->supplierAccount();
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-TEST-2', 'status' => 'accepted', 'ordered_at' => now()]);
        $this->fakeGemini(['is_certificate' => false, 'document_kind' => 'selfie', 'type' => null, 'certificate_number' => null, 'issuer' => null, 'issued_at' => null, 'expires_at' => null, 'material' => null, 'holder' => null, 'confidence' => 99]);

        $this->actingAs($account)->post(route('supplier.orders.ship', $order), ['material_id' => Material::value('id'), 'quantity_kg' => 10, 'origin_country' => 'Tunisie', 'certificate' => UploadedFile::fake()->image('selfie.jpg')])
            ->assertSessionHasErrors('certificate');

        $this->assertSame(0, $order->batches()->count());
        $this->assertSame('accepted', $order->fresh()->status);
    }

    public function test_supplier_can_decline_with_a_reason_and_cannot_ship(): void
    {
        [$supplier, $account] = $this->supplierAccount();
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-TEST-3', 'status' => 'ordered', 'ordered_at' => now()]);

        $this->actingAs($account)->post(route('supplier.orders.answer', $order), ['decision' => 'decline'])->assertSessionHasErrors('decline_reason');
        $this->post(route('supplier.orders.answer', $order), ['decision' => 'decline', 'decline_reason' => 'Stock épuisé jusqu’en décembre.'])->assertSessionHasNoErrors();

        $this->assertSame('declined', $order->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['type' => 'supplier_order_declined']);
        $this->post(route('supplier.orders.ship', $order), [])->assertStatus(410);
    }

    public function test_brand_opens_a_supplier_account_and_supplier_answers_from_his_space(): void
    {
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();

        // La marque crée le fournisseur avec un accès : identifiants affichés une seule fois.
        $this->actingAs($brandUser)->post(route('brand.suppliers.store'), [
            'name' => 'Tissage Sahel', 'country' => 'Tunisie', 'status' => 'active', 'email' => 'contact@tissage-sahel.tn',
            'phone' => '22 123 456', 'create_access' => '1',
        ])->assertSessionHas('supplier_access.password');
        $password = session('supplier_access.password');
        $supplier = Supplier::where('name', 'Tissage Sahel')->firstOrFail();
        $this->get(route('brand.suppliers.show', $supplier))->assertSee('wa.me/21622123456', false)->assertSee('Historique des SMS');
        $this->assertDatabaseHas('sms_messages', ['supplier_id' => $supplier->id, 'to' => '+21622123456', 'status' => 'logged']);
        $account = $supplier->user;
        $this->assertSame('supplier', $account->role);
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-ACC-1', 'status' => 'ordered', 'ordered_at' => now()]);
        auth()->logout();

        // Le fournisseur se connecte : mot de passe provisoire à changer d'abord.
        $this->post('/login', ['email' => 'contact@tissage-sahel.tn', 'password' => $password]);
        $this->assertAuthenticatedAs($account);
        $this->get(route('dashboard'))->assertRedirect(route('supplier.dashboard'));
        $this->get(route('supplier.dashboard'))->assertRedirect(route('supplier.password.edit'));
        $this->put(route('supplier.password.update'), ['password' => 'Fournisseur2026', 'password_confirmation' => 'Fournisseur2026'])->assertRedirect(route('supplier.dashboard'));

        // Il voit SA commande, l'accepte ; il ne peut pas ouvrir celle d'un autre fournisseur ni l'espace marque.
        $this->get(route('supplier.dashboard'))->assertOk()->assertSee('PO-ACC-1');
        $this->post(route('supplier.orders.answer', $order), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $order->fresh()->status);
        $other = Supplier::where('name', 'Filature Méditerranée')->firstOrFail()->purchaseOrders()->firstOrFail();
        $this->get(route('supplier.orders.show', $other))->assertForbidden();
        $this->get(route('brand.suppliers.index'))->assertForbidden();
    }

    public function test_supplier_credentials_are_sent_by_sms_through_the_twilio_api(): void
    {
        config(['sms.driver' => 'twilio', 'sms.twilio' => ['sid' => 'ACtest', 'token' => 'secret', 'from' => '+15005550006']]);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201)]);
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $supplier->update(['phone' => '0021698765432', 'email' => 'acces@filature.tn']);

        $this->actingAs($brandUser)->post(route('brand.suppliers.access', $supplier))->assertSessionHas('supplier_access.sms.status', 'sent');
        $password = session('supplier_access.password');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'Accounts/ACtest/Messages.json')
            && $request['To'] === '+21698765432' && str_contains($request['Body'], $password) && str_contains($request['Body'], 'acces@filature.tn'));
        $this->assertDatabaseHas('sms_messages', ['supplier_id' => $supplier->id, 'status' => 'sent', 'provider_id' => 'SM123']);
    }

    public function test_supplier_credentials_are_sent_through_the_android_sms_gateway_api(): void
    {
        config(['sms.driver' => 'smsgate', 'sms.smsgate' => ['url' => 'https://api.sms-gate.app/3rdparty/v1/messages', 'username' => 'demo', 'password' => 'secret']]);
        Http::fake(['api.sms-gate.app/*' => Http::response(['id' => 'msg-42', 'state' => 'Pending'], 202)]);
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $supplier->update(['phone' => '29 444 051', 'email' => 'acces@filature.tn']);

        $this->actingAs($brandUser)->post(route('brand.suppliers.access', $supplier))->assertSessionHas('supplier_access.sms.status', 'sent');

        Http::assertSent(fn ($request) => $request['phoneNumbers'] === ['+21629444051'] && str_contains($request['textMessage']['text'], 'acces@filature.tn'));
        $this->assertDatabaseHas('sms_messages', ['supplier_id' => $supplier->id, 'driver' => 'smsgate', 'status' => 'sent', 'provider_id' => 'msg-42']);
    }

    public function test_sms_failure_keeps_the_access_and_shows_the_error(): void
    {
        config(['sms.driver' => 'twilio', 'sms.twilio' => ['sid' => 'ACtest', 'token' => 'secret', 'from' => '+15005550006']]);
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'The number is unverified.'], 400)]);
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $supplier->update(['phone' => '98 765 432', 'email' => 'acces@filature.tn']);

        $this->actingAs($brandUser)->post(route('brand.suppliers.access', $supplier))
            ->assertSessionHas('supplier_access.sms.status', 'failed')->assertSessionHas('supplier_access.password');
        $this->assertNotNull($supplier->fresh()->user_id);
        $this->assertDatabaseHas('sms_messages', ['supplier_id' => $supplier->id, 'status' => 'failed', 'error' => 'Twilio : The number is unverified.']);
        $this->get(route('brand.suppliers.show', $supplier))->assertSee('a échoué', false);
    }

    public function test_supplier_access_cannot_reuse_an_email_of_another_account_type(): void
    {
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $supplier->update(['email' => 'consumer@textilecycle.test']);

        $this->actingAs($brandUser)->post(route('brand.suppliers.access', $supplier))->assertSessionHasErrors('email');
        $this->assertNull($supplier->fresh()->user_id);
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
