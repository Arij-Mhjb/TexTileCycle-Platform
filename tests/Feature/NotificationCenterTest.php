<?php

namespace Tests\Feature;

use App\Models\BrandNotification;
use App\Models\ConsumerNotification;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cloche de notifications commune aux trois modules : chaque rôle reçoit les événements
 * qui le concernent, les ouvre depuis l'en-tête et ne voit jamais ceux des autres.
 */
class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_supplier_is_notified_of_a_new_order_and_opens_it_from_the_bell(): void
    {
        $brand = $this->user('brand@textilecycle.test');
        $supplierAccount = $this->user('fournisseur@textilecycle.test');
        $supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        BrandNotification::where('user_id', $supplierAccount->id)->delete();

        $this->actingAs($brand)->post(route('brand.purchase-orders.store', $supplier), [
            'ordered_at' => now()->toDateString(), 'expected_at' => now()->addWeeks(3)->toDateString(), 'notes' => 'Test cloche',
            'lines' => [['material_id' => Material::where('name', 'Coton recyclé')->value('id'), 'quantity_kg' => 300]],
        ])->assertSessionHasNoErrors();
        $order = PurchaseOrder::where('notes', 'Test cloche')->firstOrFail();

        $notification = BrandNotification::where('user_id', $supplierAccount->id)->where('type', 'purchase_order_created')->firstOrFail();
        $this->assertStringContainsString($order->reference, $notification->message);

        $this->actingAs($supplierAccount)->get(route('supplier.dashboard'))->assertOk()
            ->assertSee('Notifications (1 non lues)', false)->assertSee('Nouvelle commande de');

        $this->post(route('notification-center.open', $notification->id))->assertRedirect(route('supplier.orders.show', $order));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_is_notified_of_repair_requests_trade_ins_and_offers(): void
    {
        $admin = $this->user('admin@textilecycle.test');
        $consumer = $this->user('consumer@textilecycle.test');
        BrandNotification::where('user_id', $admin->id)->delete();

        $this->actingAs($consumer)->post(route('consumer.trade-ins.store'), [
            'product_name' => 'Veste en jean', 'condition' => 'wearable', 'description' => 'Portée deux saisons',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('brand_notifications', ['user_id' => $admin->id, 'type' => 'trade_in_submitted']);
        $this->actingAs($admin)->get(route('admin.trade-ins.index'))->assertOk()->assertSee('Nouvelle demande de reprise');

        $this->post(route('notification-center.read-all'))->assertRedirect();
        $this->assertSame(0, BrandNotification::where('user_id', $admin->id)->whereNull('read_at')->count());
    }

    public function test_consumer_bell_reads_consumer_notifications_and_cannot_open_someone_elses(): void
    {
        $consumer = $this->user('consumer@textilecycle.test');
        $mine = ConsumerNotification::create(['user_id' => $consumer->id, 'type' => 'test', 'title' => 'Votre article a une seconde vie', 'message' => 'Test', 'url' => route('consumer.repairs.index')]);
        $other = ConsumerNotification::create(['user_id' => User::factory()->create(['role' => 'consumer'])->id, 'type' => 'test', 'title' => 'Privé', 'message' => 'Privé']);

        $this->actingAs($consumer)->get(route('consumer.marketplace.index'))->assertOk()->assertSee('Votre article a une seconde vie');
        $this->post(route('notification-center.open', $mine->id))->assertRedirect(route('consumer.repairs.index'));
        $this->post(route('notification-center.open', $other->id))->assertNotFound();
        $this->assertNull($other->fresh()->read_at);
    }
}
