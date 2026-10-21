<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandNotification;
use App\Models\CarbonTarget;
use App\Models\CertificateAlert;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\RecyclingLoop;
use App\Models\RecyclingSchedule;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Notifications\SupplierInvitationMail;
use App\Services\Ai\GreenClaimsService;
use App\Services\Ai\HuggingFaceClient;
use App\Services\CarbonTargetService;
use App\Services\RecyclingLoopService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Modèles Hugging Face (vision-langage, ClimateBERT, CLIP, LLM avec outils) et métiers avancés :
 * risque fournisseur + alertes, objectifs carbone, factures / avoirs PDF, collectes programmées, espace fournisseur.
 */
class AdvancedModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $brandUser;

    private User $supplierUser;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        Cache::flush();
        config(['services.gemini.key' => null]);
        RecyclingLoop::query()->delete();
        $this->brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $this->supplierUser = User::where('email', 'fournisseur@textilecycle.test')->firstOrFail();
        $this->supplier = Supplier::where('user_id', $this->supplierUser->id)->firstOrFail();
    }

    private function chatReply(array $message): array
    {
        return ['choices' => [['message' => ['role' => 'assistant'] + $message]]];
    }

    public function test_certificate_is_read_by_a_hugging_face_vision_language_model(): void
    {
        config(['huggingface.token' => 'hf_test']);
        Http::fake(['router.huggingface.co/v1/chat/completions' => Http::response($this->chatReply(['content' => "```json\n".json_encode([
            'is_certificate' => true, 'document_kind' => 'certificat GRS', 'image_description' => 'Certificat Global Recycled Standard délivré par Control Union.',
            'type' => 'GRS', 'certificate_number' => 'CU-GRS-2026-77', 'issuer' => 'Control Union', 'issued_at' => '2026-02-01', 'expires_at' => '2027-01-31',
            'material' => 'Recycled polyester', 'holder' => 'Filature Méditerranée', 'confidence' => 91,
        ])."\n```"]))]);
        $batch = SupplierBatch::where('batch_code', 'LOT-FM-2026-01')->firstOrFail();

        $this->actingAs($this->supplierUser)->post(route('supplier.batches.certificate', $batch), ['document' => UploadedFile::fake()->image('grs.jpg')])->assertSessionHasNoErrors();

        $certificate = MaterialOriginCertificate::where('certificate_number', 'CU-GRS-2026-77')->firstOrFail();
        $this->assertSame('huggingface', $certificate->ai_extraction['source']);
        $this->assertSame('Qwen/Qwen2.5-VL-7B-Instruct', $certificate->ai_extraction['model']);
        $this->assertSame('2027-01-31', $certificate->expires_at->toDateString());
        Http::assertSent(fn (HttpRequest $request) => $request['model'] === 'Qwen/Qwen2.5-VL-7B-Instruct'
            && str_starts_with($request['messages'][0]['content'][1]['image_url']['url'], 'data:image/'));
        $this->get(route('supplier.batches.index'))->assertOk()->assertSee('Hugging Face')->assertSee('Qwen/Qwen2.5-VL-7B-Instruct');
    }

    public function test_vision_model_falls_back_to_the_next_one_when_a_provider_refuses(): void
    {
        config(['huggingface.token' => 'hf_test']);
        Http::fake(['router.huggingface.co/v1/chat/completions' => Http::sequence()
            ->push(['error' => 'model not supported'], 400)
            ->push($this->chatReply(['content' => '{"is_certificate": false, "document_kind": "photo de tissu", "image_description": "Un rouleau de tissu bleu.", "confidence": 88}']))]);
        $batch = SupplierBatch::where('batch_code', 'LOT-FM-2026-02')->firstOrFail();

        $this->actingAs($this->supplierUser)->post(route('supplier.batches.certificate', $batch), ['document' => UploadedFile::fake()->image('tissu.jpg')]);

        $certificate = $batch->certificates()->reorder()->latest('id')->firstOrFail();
        $this->assertFalse($certificate->ai_extraction['is_certificate']);
        $this->assertSame('zai-org/GLM-4.5V', $certificate->ai_extraction['model']);
    }

    public function test_green_claims_combine_climatebert_zero_shot_and_the_eu_rules(): void
    {
        $service = app(GreenClaimsService::class);

        // Sans jeton : règles de la directive seules.
        $local = $service->analyse('Une veste éco-responsable et neutre en carbone. T-shirt en 60 % coton recyclé certifié GRS.');
        $this->assertSame('local', $local['source']);
        $this->assertSame(['misleading', 'specific'], array_column($local['sentences'], 'verdict'));
        $this->assertGreaterThanOrEqual(75, $local['score']);

        // Avec jeton : ClimateBERT pour l'anglais, zero-shot multilingue pour le français.
        config(['huggingface.token' => 'hf_test']);
        Http::fake([
            'router.huggingface.co/hf-inference/models/climatebert/environmental-claims' => Http::response([[['label' => 'yes', 'score' => 0.97], ['label' => 'no', 'score' => 0.03]]]),
            'router.huggingface.co/hf-inference/models/climatebert/distilroberta-base-climate-specificity' => Http::response([[['label' => 'non', 'score' => 0.9], ['label' => 'spec', 'score' => 0.1]]]),
            'router.huggingface.co/hf-inference/models/MoritzLaurer/*' => Http::response(['sequence' => 'x', 'labels' => ['une promesse écologique vague', 'une promesse écologique précise et chiffrée', 'sans rapport avec l’écologie'], 'scores' => [0.7, 0.2, 0.1]]),
        ]);
        $english = $service->analyse('Our jeans are made with care for the planet.');
        $this->assertSame('huggingface', $english['source']);
        $this->assertContains('climatebert/environmental-claims', $english['models']);
        $this->assertSame('vague', $english['sentences'][0]['verdict']);
        $this->assertSame(97, $english['sentences'][0]['ai']['claim']);

        $french = $service->analyse('Une collection pensée pour respecter la planète.');
        $this->assertSame(['MoritzLaurer/mDeBERTa-v3-base-mnli-xnli'], $french['models']);
        $this->assertSame('vague', $french['sentences'][0]['verdict']);

        $this->actingAs($this->brandUser)->post(route('brand.green-claims.analyse'), ['texte' => 'Notre manteau durable est neutre en carbone.'])
            ->assertOk()->assertSee('Trompeuse')->assertSee('Heritage Wool Coat');
    }

    public function test_clip_recognises_the_fiber_on_a_photo_and_proposes_the_material(): void
    {
        $this->actingAs($this->brandUser)->postJson(route('brand.recycling.identify-fiber'), ['photo' => UploadedFile::fake()->image('lot.jpg')])
            ->assertOk()->assertJson(['available' => false, 'matches' => []]);

        config(['huggingface.token' => 'hf_test']);
        Http::fake(['router.huggingface.co/hf-inference/models/openai/clip-vit-large-patch14' => Http::response([
            ['label' => 'a close-up photo of blue denim jeans fabric', 'score' => 0.55],
            ['label' => 'a close-up photo of plain cotton fabric', 'score' => 0.25],
            ['label' => 'a close-up photo of shiny polyester sportswear fabric', 'score' => 0.12],
            ['label' => 'a close-up photo of a wool knit sweater', 'score' => 0.08],
        ])]);

        $json = $this->postJson(route('brand.recycling.identify-fiber'), ['photo' => UploadedFile::fake()->image('lot.jpg')])->assertOk()->json();
        $this->assertSame('huggingface', $json['source']);
        $this->assertSame('Coton conventionnel', $json['matches'][0]['material']);
        $this->assertSame(80, $json['matches'][0]['confidence']); // denim + coton
        $this->assertSame(Material::where('name', 'Coton conventionnel')->value('id'), $json['matches'][0]['material_id']);
        $this->assertSame('Coton recyclé', $json['matches'][0]['route']);
    }

    public function test_copilot_llm_calls_tools_on_the_brand_data(): void
    {
        config(['huggingface.token' => 'hf_test']);
        Http::fake(['router.huggingface.co/v1/chat/completions' => Http::sequence()
            ->push($this->chatReply(['content' => null, 'tool_calls' => [['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'fournisseurs_risque', 'arguments' => '{}']]]]))
            ->push($this->chatReply(['content' => 'Le fournisseur le plus à risque est **Wool & Co Merino**.']))]);

        $json = $this->actingAs($this->brandUser)->postJson(route('brand.copilot.ask'), ['question' => 'Quel fournisseur est le plus risqué ?'])->assertOk()->json();

        $this->assertSame('huggingface', $json['source']);
        $this->assertSame('openai/gpt-oss-120b', $json['model']);
        $this->assertSame('fournisseurs_risque', $json['tools'][0]['name']);
        $this->assertStringContainsString('Wool & Co Merino', $json['answer']);
        Http::assertSentCount(2);
        Http::assertSent(fn (HttpRequest $request) => isset($request['tools'])
            && collect($request['messages'])->contains(fn ($message) => $message['role'] === 'tool' && str_contains($message['content'], $this->supplier->name)));
        $this->get(route('brand.copilot'))->assertOk()->assertSee('Copilote IA')->assertSee('openai/gpt-oss-120b');
    }

    public function test_copilot_works_locally_without_token_and_only_sees_its_brand(): void
    {
        $json = $this->actingAs($this->brandUser)->postJson(route('brand.copilot.ask'), ['question' => 'Combien m’a rapporté le recyclage et quels certificats expirent ?'])->assertOk()->json();
        $this->assertSame('local', $json['source']);
        $this->assertEqualsCanonicalizing(['certificats_expirants', 'recyclage_bilan'], array_column($json['tools'], 'name'));

        $this->actingAs($this->supplierUser)->postJson(route('brand.copilot.ask'), ['question' => 'Bonjour'])->assertForbidden();
    }

    public function test_supplier_risk_score_and_certificate_alerts_are_sent_once_per_threshold(): void
    {
        $certificate = MaterialOriginCertificate::where('certificate_number', 'RWS-NZ-8812')->firstOrFail();
        $certificate->update(['expires_at' => now()->addDays(5)->toDateString()]);
        $owner = $certificate->batch->supplier;

        $this->artisan('suppliers:certificate-alerts')->assertSuccessful();
        $this->artisan('suppliers:certificate-alerts')->assertSuccessful();

        $this->assertSame(1, CertificateAlert::where('material_origin_certificate_id', $certificate->id)->count());
        $this->assertSame(7, CertificateAlert::where('material_origin_certificate_id', $certificate->id)->value('threshold'));
        $this->assertTrue(BrandNotification::where('user_id', $this->brandUser->id)->where('type', 'certificate_expiring')->exists());

        $this->travel(6)->days();
        $this->artisan('suppliers:certificate-alerts');
        $this->assertSame([0, 7], CertificateAlert::where('material_origin_certificate_id', $certificate->id)->orderBy('threshold')->pluck('threshold')->all());
        $this->assertTrue(BrandNotification::where('user_id', $this->brandUser->id)->where('type', 'certificate_expired')->exists());

        $page = $this->actingAs($this->brandUser)->get(route('brand.suppliers-risk'))->assertOk()->assertSee($owner->name)->assertSee('Expiré')->assertSee('RWS-NZ-8812');
        $this->assertStringContainsString('Risques fournisseurs', $page->getContent());
        $this->actingAs(User::where('email', 'admin@textilecycle.test')->firstOrFail())->get(route('admin.suppliers-risk'))->assertOk()->assertSee($owner->name);
    }

    public function test_carbon_target_budget_units_and_recycled_levers(): void
    {
        $this->actingAs($this->brandUser)->post(route('brand.carbon-targets.store'), ['name' => 'AH 2026', 'season' => 'AH 2026'])->assertSessionHasErrors('budget_kg');
        $this->post(route('brand.carbon-targets.store'), ['name' => 'AH 2026 bas carbone', 'season' => 'AH 2026', 'budget_kg' => 3000, 'max_unit_kg' => 20])->assertRedirect();
        $target = CarbonTarget::firstOrFail();
        $this->assertCount(4, $target->planned_units);

        $coat = $this->brandUser->brand->products()->where('name', 'Heritage Wool Coat')->value('id');
        $this->patch(route('brand.carbon-targets.update', $target), ['units' => [$coat => 100]])->assertSessionHasNoErrors();
        $status = app(CarbonTargetService::class)->status($target->fresh()->load('brand'));

        $coatRow = $status['rows']->firstWhere('product.id', $coat);
        $this->assertSame(100, $coatRow['units']);
        $this->assertTrue($coatRow['over_unit']); // 32,4 kg > plafond de 20 kg
        $this->assertSame('Laine mérinos → Laine recyclée', $coatRow['lever']['text']);
        $this->assertGreaterThan(0, $status['savings_kg']);
        $this->assertSame('over', $status['state']);
        $this->assertLessThan($status['total_kg'], $status['after_levers_kg']);
        $this->get(route('brand.carbon-targets.show', $target))->assertOk()->assertSee('Laine recyclée')->assertSee('au-dessus du plafond');

        $other = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $other->id, 'name' => 'Autre', 'slug' => 'autre']);
        $this->actingAs($other)->get(route('brand.carbon-targets.show', $target))->assertForbidden();
    }

    public function test_final_price_gives_an_invoice_or_a_credit_note_in_pdf(): void
    {
        $service = app(RecyclingLoopService::class);
        $make = function (string $mode) use ($service) {
            $loop = $service->request($this->brandUser->brand, $this->supplier, [['kind' => 'surplus', 'material_id' => Material::where('name', 'Polyester vierge')->value('id'), 'quantity_kg' => 50]], [], ['mode' => $mode], $this->brandUser);
            $service->propose($loop, ['output_material_id' => Material::where('name', 'Polyester recyclé')->value('id'), 'expected_output_kg' => 44]);
            $service->accept($loop);
            $service->ship($loop);
            $service->receive($loop, 50);
            $this->assertFalse($loop->fresh()->hasInvoice());
            $service->transform($loop, 44, UploadedFile::fake()->image('balles.jpg'));

            return $loop->fresh();
        };

        $recover = $make('recover');
        $this->assertSame('FAC-RCY-'.now()->format('Y').'-0001', $recover->invoice_number);
        $pdf = $this->actingAs($this->brandUser)->get(route('brand.recycling.invoice', $recover))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        $this->assertStringContainsString('FACTURE', $pdf->getContent());
        $this->actingAs($this->supplierUser)->get(route('supplier.recycling.invoice', $recover))->assertOk();
        $this->actingAs($this->brandUser)->get(route('brand.recycling.show', $recover))->assertSee('Facture PDF');

        $buyback = $make('buyback');
        $this->assertSame('AV-RCY-'.now()->format('Y').'-0001', $buyback->invoice_number);
        $this->assertStringContainsString('AVOIR', $this->actingAs($this->brandUser)->get(route('brand.recycling.invoice', $buyback))->getContent());

        $pending = $service->request($this->brandUser->brand, $this->supplier, [['kind' => 'offcuts', 'material_id' => Material::where('name', 'Coton conventionnel')->value('id'), 'quantity_kg' => 10]], [], [], $this->brandUser);
        $this->get(route('brand.recycling.invoice', $pending))->assertNotFound();
    }

    public function test_scheduled_collection_sends_a_request_automatically(): void
    {
        $cotton = Material::where('name', 'Coton conventionnel')->value('id');
        $this->actingAs($this->brandUser)->post(route('brand.recycling-schedules.store'), [
            'supplier_id' => $this->supplier->id, 'mode' => 'recover', 'frequency' => 'monthly', 'day_of_month' => 5,
            'kind' => 'offcuts', 'material_id' => $cotton, 'quantity_kg' => 40,
        ])->assertSessionHasNoErrors();
        $schedule = RecyclingSchedule::firstOrFail();
        $this->assertTrue($schedule->next_run_on->isFuture() || $schedule->next_run_on->isToday());
        $this->assertSame(5, $schedule->next_run_on->day);

        $this->artisan('recycling:run-schedules')->expectsOutputToContain('0 collecte');
        $this->travelTo($schedule->next_run_on->copy()->setTime(8, 0));
        $this->artisan('recycling:run-schedules')->expectsOutputToContain('1 collecte');

        $loop = RecyclingLoop::where('recycling_schedule_id', $schedule->id)->firstOrFail();
        $this->assertSame('requested', $loop->status);
        $this->assertSame(40.0, $loop->totalKg());
        $this->assertNotNull($loop->pricing);
        $this->assertSame($schedule->next_run_on->copy()->addMonthNoOverflow()->toDateString(), $schedule->fresh()->next_run_on->toDateString());
        $this->assertTrue(BrandNotification::where('user_id', $this->supplierUser->id)->where('type', 'recycling_requested')->exists());

        $this->patch(route('brand.recycling-schedules.toggle', $schedule))->assertSessionHasNoErrors();
        $this->assertFalse($schedule->fresh()->active);
        $this->get(route('brand.recycling-schedules.index'))->assertOk()->assertSee('Collectes programmées')->assertSee('En pause');
        $this->actingAs($this->supplierUser)->patch(route('brand.recycling-schedules.toggle', $schedule))->assertForbidden();
    }

    public function test_no_credits_or_unsupported_model_stop_the_calls_and_keep_pages_fast(): void
    {
        config(['huggingface.token' => 'hf_test']);
        Http::fake([
            'router.huggingface.co/v1/chat/completions' => Http::response(['error' => 'You have no remaining credits.'], 402),
            'router.huggingface.co/hf-inference/models/openai/*' => Http::response(['error' => 'Model not supported by provider hf-inference'], 400),
        ]);

        // 402 : un seul appel, puis plus aucun (les autres modèles ne sont pas essayés).
        $this->actingAs($this->brandUser)->postJson(route('brand.copilot.ask'), ['question' => 'Quels fournisseurs sont à risque ?'])->assertOk()->assertJson(['source' => 'local']);
        $this->postJson(route('brand.copilot.ask'), ['question' => 'Et le recyclage ?'])->assertOk()->assertJson(['source' => 'local']);
        Http::assertSentCount(1);
        $this->assertSame('no_credits', app(HuggingFaceClient::class)->status()['state']);
        $this->get(route('brand.copilot'))->assertOk()->assertSee('Crédits Hugging Face épuisés');

        // Modèle non servi : mémorisé, plus appelé.
        app(HuggingFaceClient::class)->reset();
        $this->postJson(route('brand.recycling.identify-fiber'), ['photo' => UploadedFile::fake()->image('a.jpg')])->assertJson(['available' => false]);
        $this->postJson(route('brand.recycling.identify-fiber'), ['photo' => UploadedFile::fake()->image('b.jpg')])->assertJson(['available' => false]);
        Http::assertSentCount(2);
    }

    public function test_clip_scores_computed_in_the_browser_are_checked_and_stored(): void
    {
        Http::fake();
        $this->actingAs($this->brandUser)->post(route('brand.recycling.store'), [
            'supplier_id' => $this->supplier->id, 'mode' => 'recover',
            'items' => [[
                'kind' => 'defective', 'material_id' => Material::where('name', 'Coton conventionnel')->value('id'), 'pieces' => 10, 'quantity_kg' => 8,
                'issue' => 'Tache', 'photo' => UploadedFile::fake()->image('veste.jpg'),
                'clip_scores' => json_encode(['a photo of fabric with a stain' => 0.61, 'a photo of clean fabric rolls in perfect condition' => 0.2, 'ignore me' => 0.99]),
            ]],
        ])->assertSessionHasNoErrors();

        $analysis = RecyclingLoop::firstOrFail()->items()->firstOrFail()->ai_analysis;
        $this->assertSame('huggingface', $analysis['source']);
        $this->assertSame('Xenova/clip-vit-base-patch32 (navigateur)', $analysis['model']);
        $this->assertSame('Tache', $analysis['findings'][0]['label']);
        $this->assertTrue($analysis['consistent']);
        $this->assertNotContains('ignore me', array_column($analysis['findings'], 'label'));
        Http::assertNothingSent();
        $this->get(route('brand.recycling.create'))->assertOk()->assertSee('Xenova/clip-vit-base-patch32')->assertSee('@huggingface/transformers@3.7.6', false);
    }

    public function test_new_supplier_receives_the_activation_link_by_email(): void
    {
        Notification::fake();
        $this->actingAs($this->brandUser)->post(route('brand.suppliers.store'), [
            'name' => 'Yakine Mhamdi', 'email' => 'yakine@filature.test', 'country' => 'Tunisie', 'city' => 'Tunis', 'create_access' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('supplier_invitation', fn ($invitation) => $invitation['mail']['sent'] === true);

        Notification::assertSentOnDemand(SupplierInvitationMail::class, function ($notification, $channels, $notifiable) {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === 'yakine@filature.test' && str_contains($notification->url, '/invitation-fournisseur/')
                && $mail->actionText === 'Activer mon espace fournisseur' && str_contains($mail->subject, $this->brandUser->brand->name);
        });
        $supplier = Supplier::where('email', 'yakine@filature.test')->firstOrFail();
        $this->get(route('brand.suppliers.show', $supplier))->assertSee('Lien d’activation envoyé', false)->assertSee('yakine@filature.test');
    }

    public function test_supplier_space_uses_the_console_layout_with_counters(): void
    {
        $this->actingAs($this->supplierUser)->get(route('supplier.dashboard'))->assertOk()
            ->assertSee('Mon activité')->assertSee('Lots &amp; certificats', false)->assertSee('À faire maintenant')->assertSee('Vous travaillez pour');
        $this->get(route('supplier.batches.index'))->assertOk()->assertSee('Fournisseur')->assertSee('image/jpeg,image/png', false)->assertDontSee('accept="image/*', false);
    }
}
