<?php

namespace Tests\Feature;

use App\Jobs\AnalyseBrandApplication;
use App\Models\Brand;
use App\Models\BrandApplication;
use App\Models\Label;
use App\Models\User;
use App\Notifications\BrandApplicationMail;
use App\Services\BrandLabelAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\Support\BrandQuestionnaireData;
use Tests\TestCase;

class BrandOnboardingTest extends TestCase
{
    use BrandQuestionnaireData, RefreshDatabase;

    private function application(array $attributes = []): BrandApplication
    {
        $user = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $user->id, 'name' => 'Atelier Circulaire', 'slug' => 'atelier-'.$user->id,
            'onboarding_status' => 'pending', 'is_active' => false]);

        return BrandApplication::create(array_merge(['brand_id' => $brand->id, 'answers' => $this->brandAnswers(),
            'status' => 'pending', 'analysis_status' => 'unavailable'], $attributes));
    }

    public function test_registration_stores_only_relevant_answers_and_queues_receipt_and_analysis(): void
    {
        config(['brand_onboarding.ai_key' => 'test-key']);
        Notification::fake();
        Queue::fake();
        $this->post('/register', ['name' => 'Responsable', 'email' => 'new@example.test', 'role' => 'brand',
            'brand_name' => 'Nouvelle', 'password' => 'password', 'password_confirmation' => 'password',
            'analysis_consent' => '1', 'answers' => $this->brandAnswers(['recycled_percent' => '95', 'vegan_plan' => 'Réponse obsolète'])])
            ->assertSessionHasNoErrors()->assertRedirect(route('brand.application-pending'));
        $this->assertGuest();
        $application = BrandApplication::firstOrFail();
        $this->assertArrayNotHasKey('recycled_percent', $application->answers);
        $this->assertArrayNotHasKey('vegan_plan', $application->answers);
        $this->assertFalse($application->brand->is_active);
        $this->assertSame('pending', $application->brand->onboarding_status);
        Queue::assertPushed(AnalyseBrandApplication::class);
        Notification::assertSentTo($application->brand->user, BrandApplicationMail::class,
            fn ($mail) => $mail->subject === 'Votre demande marque est prise en charge');
        $this->get(route('brand.application-pending'))->assertOk()->assertSee('en cours de traitement');
        $this->get(route('brands.show', $application->brand))->assertNotFound();
    }

    public function test_nested_questions_are_validated_before_any_account_is_created(): void
    {
        Queue::fake();
        Notification::fake();
        $this->post('/register', ['name' => 'Responsable', 'email' => 'invalid@example.test', 'role' => 'brand',
            'brand_name' => 'Incomplète', 'password' => 'password', 'password_confirmation' => 'password',
            'analysis_consent' => '1', 'answers' => $this->brandAnswers(['animal' => 'yes', 'animal_details' => 'Cuir',
                'animal_traceability' => 'unknown', 'vegan_transition' => 'yes'])])
            ->assertSessionHasErrors('vegan_plan');
        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
        Queue::assertNothingPushed();
    }

    public function test_registration_without_ai_key_can_be_reviewed_and_receipt_is_queued(): void
    {
        config(['brand_onboarding.ai_key' => null]);
        Queue::fake();
        $this->post('/register', ['name' => 'Responsable', 'email' => 'manual@example.test', 'role' => 'brand',
            'brand_name' => 'Revue manuelle', 'password' => 'password', 'password_confirmation' => 'password',
            'analysis_consent' => '1', 'answers' => $this->brandAnswers()])->assertSessionHasNoErrors();
        $application = BrandApplication::firstOrFail();
        $this->assertSame('unavailable', $application->analysis_status);
        Queue::assertNotPushed(AnalyseBrandApplication::class);
        Queue::assertPushed(\Illuminate\Notifications\SendQueuedNotifications::class);
        $mail = new BrandApplicationMail('Prise en charge', ['Dossier reçu.'], route('login'), 'Se connecter');
        $html = $mail->toMail($application->brand->user)->render();
        $this->assertStringContainsString('Dossier reçu.', (string) $html);
        $this->assertStringContainsString(route('login'), (string) $html);
    }

    public function test_consent_is_required_and_percentages_are_bounded(): void
    {
        Notification::fake();
        Queue::fake();
        $payload = ['name' => 'Responsable', 'email' => 'consent@example.test', 'role' => 'brand',
            'brand_name' => 'Validation', 'password' => 'password', 'password_confirmation' => 'password',
            'answers' => $this->brandAnswers()];
        $this->post('/register', $payload)->assertSessionHasErrors('analysis_consent');
        $payload['analysis_consent'] = '1';
        $payload['answers'] = $this->brandAnswers(['recycled' => 'yes', 'recycled_percent' => '120',
            'recycled_origin' => 'post', 'chain' => 'yes']);
        $this->post('/register', $payload)->assertSessionHasErrors('recycled_percent');
        $this->assertDatabaseMissing('users', ['email' => 'consent@example.test']);
    }

    public function test_analysis_failure_is_visible_and_can_be_retried_by_admin(): void
    {
        Queue::fake();
        $application = $this->application(['analysis_status' => 'pending']);
        (new AnalyseBrandApplication($application->id))->failed(new \RuntimeException('API unavailable'));
        $this->assertSame('failed', $application->fresh()->analysis_status);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.brand-applications.analyse', $application))->assertRedirect();
        $this->assertSame('pending', $application->fresh()->analysis_status);
        Queue::assertPushed(AnalyseBrandApplication::class);
    }

    public function test_consumer_registration_does_not_require_a_questionnaire(): void
    {
        $this->post('/register', ['name' => 'Client', 'email' => 'client@example.test', 'role' => 'consumer',
            'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect(route('consumer.home'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('brand_applications', 0);
    }

    public function test_duplicate_registration_email_has_a_clear_french_error(): void
    {
        app()->setLocale('fr');
        User::factory()->create(['email' => 'existing@example.test']);

        $this->post('/register', [
            'name' => 'Responsable', 'email' => 'existing@example.test', 'role' => 'consumer',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Cette adresse e-mail est déjà utilisée. Connectez-vous ou choisissez une autre adresse.',
        ]);
    }

    public function test_pending_brand_cannot_login_or_use_an_existing_session(): void
    {
        $application = $this->application();
        $this->post('/login', ['email' => $application->brand->user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($application->brand->user)->get(route('brand.dashboard'))->assertRedirect(route('brand.application-pending'));
        $this->actingAs($application->brand->user)->post(route('brand.material-requests.store'), [])->assertRedirect(route('brand.application-pending'));
    }

    public function test_admin_can_replace_ai_selection_and_activate_account_once_with_audit_email(): void
    {
        Notification::fake();
        $recommended = Label::where('slug', 'vegan-trademark')->firstOrFail();
        $selected = Label::where('slug', 'eu-ecolabel-textiles')->firstOrFail();
        $recommendations = [['label_id' => $recommended->id, 'label_name' => $recommended->name,
            'official_url' => $recommended->official_url, 'path' => 'potential', 'reason' => 'Déclarations sans matière animale',
            'gaps' => ['Contrôler les colles'], 'next_steps' => ['Réunir les déclarations fournisseurs']]];
        $application = $this->application(['analysis_status' => 'completed', 'recommendations' => $recommendations]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.brand-applications.index'))->assertOk()->assertSee('Atelier Circulaire');
        $this->get(route('admin.brand-applications.show', $application))->assertOk()->assertSee($recommended->name);
        $this->post(route('admin.brand-applications.approve', $application), ['labels' => [$selected->id], 'review_note' => 'Retenir Ecolabel, contrôles requis.'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertSame($recommendations, $application->recommendations);
        $this->assertSame([$selected->id], $application->brand->approvedLabels()->pluck('labels.id')->all());
        $this->assertSame($selected->name, $application->approved_label_snapshot[0]['name']);
        Notification::assertSentTo($application->brand->user, BrandApplicationMail::class);
        Notification::assertSentOnDemand(BrandApplicationMail::class, fn ($mail, $channels, $recipient) => $recipient->routes['mail'] === 'arij.mhjb1@gmail.com' && str_contains(implode(' ', $mail->lines), 'Contrôler les colles'));
        $this->post(route('admin.brand-applications.approve', $application), ['labels' => [$selected->id], 'review_note' => 'Doublon'])->assertStatus(409);
        Notification::assertCount(2);
        $this->post('/logout');
        $this->post('/login', ['email' => $application->brand->user->email, 'password' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect(route('brand.dashboard', absolute: false));
        $this->get(route('brand.labels.index'))->assertOk()->assertSee($selected->name)->assertDontSee($recommended->name);
        $this->post(route('brand.labels.apply', $recommended))->assertForbidden();
        $this->post(route('brand.labels.apply', $selected))->assertRedirect(route('brand.labels.show', $selected));
    }

    public function test_no_label_selection_is_a_valid_recorded_decision(): void
    {
        Notification::fake();
        $application = $this->application();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.brand-applications.approve', $application),
            ['selection_submitted' => '1', 'review_note' => 'Aucune piste avant documentation.'])->assertSessionHasNoErrors();
        $this->assertSame([], $application->fresh()->approved_label_snapshot);
    }

    public function test_review_is_admin_only_and_cannot_approve_during_analysis(): void
    {
        $application = $this->application(['analysis_status' => 'pending']);
        $this->actingAs(User::factory()->create(['role' => 'consumer']))->get(route('admin.brand-applications.show', $application))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.brand-applications.approve', $application),
            ['labels' => [], 'review_note' => 'Trop tôt'])->assertStatus(409);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_ai_response_is_validated_and_animal_material_requires_transition(): void
    {
        config(['brand_onboarding.ai_key' => 'test-key']);
        $label = Label::where('slug', 'vegan-trademark')->firstOrFail();
        $application = $this->application(['answers' => $this->brandAnswers(['animal' => 'yes', 'animal_details' => 'Cuir',
            'vegan_transition' => 'yes', 'vegan_plan' => 'Substituer le cuir'])]);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'summary' => 'Étudier une gamme sans composants animaux', 'recommendations' => [[
                'label_id' => $label->id, 'path' => 'potential', 'reason' => 'Projet de substitution',
                'gaps' => ['Cuir animal actuel'], 'next_steps' => ['Contrôler les alternatives et les colles'],
            ]],
        ])]]]])]);
        app(BrandLabelAdvisor::class)->analyse($application);
        $application->refresh();
        $this->assertSame('completed', $application->analysis_status);
        $this->assertSame('transition', $application->recommendations[0]['path']);
        $this->assertSame($label->name, $application->recommendations[0]['label_name']);
        Http::assertSent(fn ($request) => ! str_contains($request->body(), $application->brand->user->email));
    }

    public function test_missing_ai_configuration_does_not_invent_recommendations(): void
    {
        config(['brand_onboarding.ai_key' => null]);
        Http::fake();
        $application = $this->application();
        app(BrandLabelAdvisor::class)->analyse($application);
        $this->assertSame('unavailable', $application->fresh()->analysis_status);
        $this->assertNull($application->fresh()->recommendations);
        Http::assertNothingSent();
    }

    public function test_ai_cannot_assign_a_label_outside_the_active_catalogue(): void
    {
        config(['brand_onboarding.ai_key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'summary' => 'Réponse invalide', 'recommendations' => [[
                'label_id' => 999999, 'path' => 'potential', 'reason' => 'Invention', 'gaps' => [], 'next_steps' => [],
            ]],
        ])]]]])]);
        $this->expectException(ValidationException::class);
        app(BrandLabelAdvisor::class)->analyse($this->application());
    }
}
