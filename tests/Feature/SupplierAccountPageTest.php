<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Page « Mon compte » du fournisseur : coordonnées, identifiant de connexion et mot de passe. */
class SupplierAccountPageTest extends TestCase
{
    use RefreshDatabase;

    private User $account;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->account = User::where('email', 'fournisseur@textilecycle.test')->firstOrFail();
        $this->supplier = Supplier::where('user_id', $this->account->id)->firstOrFail();
    }

    public function test_supplier_updates_his_information_seen_by_the_brand(): void
    {
        $this->actingAs($this->account)->get(route('supplier.account.edit'))->assertOk()
            ->assertSee('Mon compte')->assertSee('Mes informations')->assertSee('EcoFashion');

        $this->put(route('supplier.account.update'), ['name' => 'Sami Ben Salah', 'phone' => '+216 22 000 111', 'country' => 'Tunisie', 'city' => 'Monastir', 'address' => 'Zone industrielle'])
            ->assertRedirect(route('supplier.account.edit'))->assertSessionHasNoErrors();

        $supplier = $this->supplier->fresh();
        $this->assertSame(['Sami Ben Salah', '+216 22 000 111', 'Monastir', 'Zone industrielle'], [$supplier->name, $supplier->phone, $supplier->city, $supplier->address]);
        $this->assertSame('Sami Ben Salah', $this->account->fresh()->name);
        $this->put(route('supplier.account.update'), ['name' => '', 'country' => ''])->assertSessionHasErrors(['name', 'country']);

        // Statut et notes internes restent du ressort de la marque.
        $this->put(route('supplier.account.update'), ['name' => 'Sami Ben Salah', 'country' => 'Tunisie', 'status' => 'suspended', 'notes' => 'piraté']);
        $this->assertSame('active', $supplier->fresh()->status);
        $this->assertNotSame('piraté', $supplier->fresh()->notes);
    }

    public function test_changing_the_login_email_requires_the_current_password(): void
    {
        $this->actingAs($this->account);
        $this->put(route('supplier.account.email'), ['email' => 'sami@filature.tn', 'current_password' => 'mauvais'])->assertSessionHasErrorsIn('email', 'current_password');
        $this->put(route('supplier.account.email'), ['email' => 'brand@textilecycle.test', 'current_password' => 'password'])->assertSessionHasErrorsIn('email', 'email');
        $this->put(route('supplier.account.email'), ['email' => 'sami@filature.tn', 'current_password' => 'password'])->assertSessionHasNoErrors();

        $this->assertSame('sami@filature.tn', $this->account->fresh()->email);
        $this->assertSame('sami@filature.tn', $this->supplier->fresh()->email);
        auth()->logout();
        $this->post('/login', ['email' => 'sami@filature.tn', 'password' => 'password']);
        $this->assertAuthenticatedAs($this->account);
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $this->actingAs($this->account);
        $this->put(route('supplier.password.update'), ['current_password' => 'mauvais', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])
            ->assertSessionHasErrorsIn('password', 'current_password');
        $this->put(route('supplier.password.update'), ['current_password' => 'password', 'password' => 'court', 'password_confirmation' => 'court'])
            ->assertSessionHasErrorsIn('password', 'password');
        $this->put(route('supplier.password.update'), ['current_password' => 'password', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])
            ->assertRedirect(route('supplier.account.edit'));

        $this->assertTrue(Hash::check('Nouveau2026', $this->account->fresh()->password));
        $this->get(route('supplier.password.edit'))->assertRedirect();
    }

    public function test_only_suppliers_reach_the_account_page(): void
    {
        $this->actingAs(User::where('email', 'brand@textilecycle.test')->firstOrFail())->get(route('supplier.account.edit'))->assertForbidden();
    }
}
