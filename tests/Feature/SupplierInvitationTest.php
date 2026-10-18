<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierInvitation;
use App\Models\User;
use Database\Factories\BrandFactory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Accès fournisseur par invitation : lien d'activation à usage unique (72 h),
 * le fournisseur choisit son mot de passe, la marque ne le voit jamais.
 */
class SupplierInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $brandUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
    }

    /** La marque crée le fournisseur avec un accès et récupère le lien d'activation. */
    private function inviteNewSupplier(): array
    {
        $this->actingAs($this->brandUser)->post(route('brand.suppliers.store'), [
            'name' => 'Tissage Sahel', 'country' => 'Tunisie', 'status' => 'active', 'email' => 'contact@tissage-sahel.tn',
            'phone' => '22 123 456', 'create_access' => '1',
        ])->assertSessionHas('supplier_invitation.url');

        return [Supplier::where('name', 'Tissage Sahel')->firstOrFail(), session('supplier_invitation.url')];
    }

    public function test_supplier_activates_his_account_from_the_link_and_answers_an_order(): void
    {
        [$supplier, $url] = $this->inviteNewSupplier();
        $this->assertNull(session('supplier_invitation.password'));
        $this->get(route('brand.suppliers.show', $supplier))->assertOk()
            ->assertSee('Envoyez ce lien d’activation', false)->assertSee('wa.me/21622123456', false)->assertSee('Invitation envoyée');

        // Avant activation, personne ne peut se connecter : le mot de passe initial est aléatoire et inconnu.
        $account = $supplier->user;
        $this->assertSame('supplier', $account->role);
        auth()->logout();
        $this->post('/login', ['email' => 'contact@tissage-sahel.tn', 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();

        // Le fournisseur ouvre le lien et choisit son mot de passe.
        $this->get($url)->assertOk()->assertSee('Activez votre accès fournisseur')->assertSee('contact@tissage-sahel.tn');
        $this->post($url, ['password' => 'court', 'password_confirmation' => 'court'])->assertSessionHasErrors('password');
        $this->post($url, ['password' => 'Sahel2026', 'password_confirmation' => 'Sahel2026'])->assertRedirect(route('supplier.dashboard'));
        $this->assertAuthenticatedAs($account);
        $this->assertFalse((bool) $account->fresh()->must_change_password);
        $this->assertNotNull($supplier->latestInvitation()->first()->used_at);

        // Le lien ne sert plus une deuxième fois.
        auth()->logout();
        $this->get($url)->assertOk()->assertSee('déjà été utilisé', false);
        $this->post($url, ['password' => 'Pirate2026', 'password_confirmation' => 'Pirate2026'])->assertSessionHasErrors('password');
        $this->assertGuest();

        // Il se connecte avec SON mot de passe et répond à une commande de la marque.
        $order = $supplier->purchaseOrders()->create(['brand_id' => $supplier->brand_id, 'reference' => 'PO-INV-1', 'status' => 'ordered', 'ordered_at' => now()]);
        $this->post('/login', ['email' => 'contact@tissage-sahel.tn', 'password' => 'Sahel2026']);
        $this->assertAuthenticatedAs($account);
        $this->get(route('supplier.dashboard'))->assertOk()->assertSee('PO-INV-1');
        $this->post(route('supplier.orders.answer', $order), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $order->fresh()->status);
        $this->get(route('brand.suppliers.index'))->assertForbidden();
    }

    public function test_link_expires_after_72_hours_and_a_new_invitation_replaces_the_old_one(): void
    {
        [$supplier, $firstUrl] = $this->inviteNewSupplier();

        $this->travel(SupplierInvitation::VALIDITY_HOURS + 1)->hours();
        $this->get($firstUrl)->assertOk()->assertSee('Ce lien a expiré');

        $this->post(route('brand.suppliers.access', $supplier))->assertSessionHas('supplier_invitation.url');
        $secondUrl = session('supplier_invitation.url');
        $this->assertNotSame($firstUrl, $secondUrl);
        $this->get($firstUrl)->assertSee('remplacé', false);
        $this->get($secondUrl)->assertSee('Activez votre accès fournisseur');
        $this->assertSame(1, $supplier->invitations()->whereNull('revoked_at')->count());
    }

    public function test_only_a_hash_of_the_token_is_stored(): void
    {
        [, $url] = $this->inviteNewSupplier();
        $token = basename($url);

        $this->assertDatabaseMissing('supplier_invitations', ['token_hash' => $token]);
        $this->assertDatabaseHas('supplier_invitations', ['token_hash' => hash('sha256', $token)]);
        $this->get(route('supplier.invitation.show', 'jeton-invente'))->assertOk()->assertSee('introuvable');
    }

    public function test_link_is_also_sent_by_sms_when_a_real_provider_is_configured(): void
    {
        config(['sms.driver' => 'twilio', 'sms.twilio' => ['sid' => 'ACtest', 'token' => 'secret', 'from' => '+15005550006']]);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201)]);

        [$supplier, $url] = $this->inviteNewSupplier();

        Http::assertSent(fn ($request) => $request['To'] === '+21622123456' && str_contains($request['Body'], $url));
        $stored = $supplier->smsMessages()->firstOrFail();
        $this->assertSame('sent', $stored->status);
        $this->assertStringNotContainsString($url, $stored->body);
        $this->assertStringContainsString('[lien d’activation]', $stored->body);
    }

    public function test_demo_mode_sends_no_sms_and_another_brand_cannot_invite(): void
    {
        [$supplier] = $this->inviteNewSupplier();
        $this->assertSame(0, $supplier->smsMessages()->count());

        $otherBrandUser = User::factory()->create(['role' => 'brand']);
        BrandFactory::new()->create(['user_id' => $otherBrandUser->id]);
        $this->actingAs($otherBrandUser)->post(route('brand.suppliers.access', $supplier))->assertForbidden();
    }
}
