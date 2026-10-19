<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Factories\BrandFactory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cycle d'achat complet : commande multi-lignes, contre-proposition, livraisons partielles,
 * tolérances de quantité, réception, solde avec reliquat et cloisonnement des accès.
 */
class PurchaseOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $brandUser;

    private Supplier $supplier;

    private User $supplierAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['services.gemini.enabled' => true, 'services.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['output_text' => json_encode([
            'is_certificate' => true, 'document_kind' => 'certificat GRS', 'type' => 'GRS', 'certificate_number' => 'CU-GRS-'.random_int(1000, 9999),
            'issuer' => 'Control Union', 'issued_at' => '2026-03-01', 'expires_at' => '2027-03-01', 'material' => 'Recycled cotton', 'holder' => 'Filature Méditerranée', 'confidence' => 92,
        ])])]);

        $this->brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $this->supplier = Supplier::where('name', 'Filature Méditerranée')->firstOrFail();
        $this->supplierAccount = User::where('email', 'fournisseur@textilecycle.test')->firstOrFail();
    }

    private function material(string $name): int
    {
        return Material::where('name', $name)->value('id');
    }

    /** La marque passe une commande de 500 kg de coton et 200 kg de polyester. */
    private function placeOrder(): PurchaseOrder
    {
        $this->actingAs($this->brandUser)->post(route('brand.purchase-orders.store', $this->supplier), [
            'ordered_at' => now()->toDateString(), 'expected_at' => now()->addWeeks(4)->toDateString(), 'notes' => 'Collection printemps',
            'lines' => [
                ['material_id' => $this->material('Coton recyclé'), 'quantity_kg' => 500, 'unit_price' => 13.5],
                ['material_id' => $this->material('Polyester recyclé'), 'quantity_kg' => 200, 'unit_price' => 9.8],
            ],
        ])->assertSessionHasNoErrors();

        return PurchaseOrder::where('notes', 'Collection printemps')->with('lines')->firstOrFail();
    }

    private function ship(PurchaseOrder $order, string $material, float $kg)
    {
        return $this->actingAs($this->supplierAccount)->post(route('supplier.orders.ship', $order), [
            'purchase_order_line_id' => $order->lines()->where('material_id', $this->material($material))->value('id'),
            'quantity_kg' => $kg, 'origin_country' => 'Tunisie', 'certificate' => UploadedFile::fake()->image('grs.jpg'),
        ]);
    }

    public function test_full_story_counter_proposal_partial_deliveries_and_reception(): void
    {
        $order = $this->placeOrder();
        $this->assertSame('ordered', $order->status);
        $this->assertSame(8710.0, (float) $order->total_amount); // 500 × 13,5 + 200 × 9,8

        // Le fournisseur ne peut livrer que 120 kg de polyester, deux semaines plus tard.
        $polyester = $order->lines->firstWhere('material_id', $this->material('Polyester recyclé'));
        $newDate = now()->addWeeks(6)->toDateString();
        $this->actingAs($this->supplierAccount)->get(route('supplier.orders.show', $order))->assertOk()->assertSee('Proposer des modifications');
        $this->post(route('supplier.orders.answer', $order), [
            'decision' => 'counter', 'confirmed' => [$polyester->id => 120], 'proposed_expected_at' => $newDate, 'supplier_note' => 'Stock de polyester limité',
        ])->assertSessionHasNoErrors();
        $this->assertSame('countered', $order->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $this->brandUser->id, 'type' => 'supplier_order_countered']);
        $this->post(route('supplier.orders.ship', $order), [])->assertStatus(410);

        // La marque voit la proposition et l'accepte : quantité, date et montant mis à jour.
        $this->actingAs($this->brandUser)->get(route('brand.purchase-orders.show', $order))->assertOk()->assertSee('propose des modifications')->assertSee('120 kg');
        $this->post(route('brand.purchase-orders.counter', $order), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $order->refresh()->load('lines.batches');
        $this->assertSame('accepted', $order->status);
        $this->assertSame($newDate, $order->expected_at->toDateString());
        $this->assertSame(7926.0, (float) $order->total_amount); // 500 × 13,5 + 120 × 9,8
        $this->assertSame(620.0, $order->dueKg());

        // Première livraison partielle de coton.
        $this->ship($order, 'Coton recyclé', 300)->assertSessionHasNoErrors();
        $this->assertSame('partially_shipped', $order->fresh()->status);

        // Sur-livraison refusée : il reste 200 kg, tolérance +5 % ⇒ 225 kg maximum (500 × 1,05 − 300).
        $this->ship($order, 'Coton recyclé', 240)->assertSessionHasErrors('quantity_kg');
        $this->ship($order, 'Coton recyclé', 200)->assertSessionHasNoErrors();
        $this->ship($order, 'Polyester recyclé', 119)->assertSessionHasNoErrors(); // 119/120 : dans la tolérance de −2 %
        $order->refresh()->load('lines.batches');
        $this->assertSame('shipped', $order->status);
        $this->assertSame(3, $order->lines->flatMap->batches->count());

        // La marque réceptionne chaque livraison ; la commande passe « Reçue » à la dernière.
        foreach ($order->lines->flatMap->batches as $batch) {
            $this->actingAs($this->brandUser)->post(route('brand.purchase-orders.receive', $batch))->assertSessionHasNoErrors();
        }
        $order->refresh();
        $this->assertSame('received', $order->status);
        $this->assertNotNull($order->received_at);
        $this->assertSame(100, $order->receivedPercent());

        // Le journal raconte toute l'histoire, visible des deux côtés.
        $this->assertSame(['created', 'countered', 'counter_accepted', 'shipped', 'shipped', 'shipped', 'received', 'received', 'received'],
            $order->events()->reorder('id')->pluck('type')->all());
        $this->actingAs($this->supplierAccount)->get(route('supplier.orders.show', $order))->assertOk()->assertSee('Contre-proposition : Polyester recyclé : 120 kg au lieu de 200 kg');
    }

    public function test_brand_rejects_the_counter_proposal_and_the_order_is_cancelled(): void
    {
        $order = $this->placeOrder();
        $cotton = $order->lines->firstWhere('material_id', $this->material('Coton recyclé'));
        $this->actingAs($this->supplierAccount)->post(route('supplier.orders.answer', $order), ['decision' => 'counter', 'confirmed' => [$cotton->id => 300]])->assertSessionHasNoErrors();

        $this->actingAs($this->brandUser)->post(route('brand.purchase-orders.counter', $order), ['decision' => 'reject'])->assertSessionHasErrors('reason');
        $this->post(route('brand.purchase-orders.counter', $order), ['decision' => 'reject', 'reason' => '300 kg ne suffisent pas pour la collection'])->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('300 kg ne suffisent pas pour la collection', $order->fresh()->close_reason);
    }

    public function test_counter_proposal_must_change_something_and_cannot_confirm_nothing(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($this->supplierAccount)->post(route('supplier.orders.answer', $order), ['decision' => 'counter'])->assertSessionHasErrors('decision');
        $zero = $order->lines->mapWithKeys(fn ($line) => [$line->id => 0])->all();
        $this->post(route('supplier.orders.answer', $order), ['decision' => 'counter', 'confirmed' => $zero])->assertSessionHasErrors('decision');
        $this->assertSame('ordered', $order->fresh()->status);
    }

    public function test_brand_closes_a_partially_delivered_order_and_cannot_cancel_it(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($this->supplierAccount)->post(route('supplier.orders.answer', $order), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->ship($order, 'Coton recyclé', 500)->assertSessionHasNoErrors();
        $this->assertSame('partially_shipped', $order->fresh()->status);

        $this->actingAs($this->brandUser)->patch(route('brand.purchase-orders.update', $order), ['status' => 'cancelled'])->assertSessionHasErrors('status');
        $this->post(route('brand.purchase-orders.close', $order), [])->assertSessionHasErrors('close_reason');
        $this->post(route('brand.purchase-orders.close', $order), ['close_reason' => 'Polyester remplacé par un autre fournisseur'])->assertSessionHasNoErrors();
        $this->assertSame('shipped', $order->fresh()->status);

        $batch = $order->fresh()->batches()->firstOrFail();
        $this->post(route('brand.purchase-orders.receive', $batch))->assertSessionHasNoErrors();
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame('Soldée avec reliquat', $order->fresh()->statusLabel());
        $this->assertDatabaseHas('purchase_order_events', ['purchase_order_id' => $order->id, 'type' => 'closed_short']);
    }

    public function test_orders_are_isolated_between_suppliers_and_brands(): void
    {
        $order = $this->placeOrder();
        $intruder = User::factory()->create(['role' => 'supplier']);
        Supplier::where('name', 'Linificio Nord')->firstOrFail()->update(['user_id' => $intruder->id]);

        $this->actingAs($intruder)->get(route('supplier.orders.show', $order))->assertForbidden();
        $this->post(route('supplier.orders.answer', $order), ['decision' => 'accept'])->assertForbidden();

        $otherBrandUser = User::factory()->create(['role' => 'brand']);
        BrandFactory::new()->create(['user_id' => $otherBrandUser->id]);
        $this->actingAs($otherBrandUser)->get(route('brand.purchase-orders.show', $order))->assertForbidden();
        $this->assertSame('ordered', $order->fresh()->status);
    }

    public function test_brand_never_creates_lots_and_is_invited_to_activate_the_supplier(): void
    {
        $linen = Supplier::where('name', 'Linificio Nord')->firstOrFail();

        // Plus aucune route marque pour créer, supprimer un lot ou y ajouter un certificat.
        $this->actingAs($this->brandUser)->post('/brand/suppliers/'.$linen->id.'/batches', ['material_id' => $this->material('Lin européen'), 'quantity_kg' => 60, 'origin_country' => 'France'])->assertNotFound();
        $batch = $linen->batches()->firstOrFail();
        $this->post('/brand/supplier-batches/'.$batch->id.'/certificates', [])->assertClientError();
        $this->delete('/brand/supplier-batches/'.$batch->id)->assertClientError();

        $this->get(route('brand.suppliers.show', $linen))->assertOk()
            ->assertSee('n’a pas encore activé son accès', false)->assertSee('Inviter le fournisseur')
            ->assertDontSee('Saisir la livraison')->assertDontSee('Envoyer à l’IA', false);
    }

    public function test_supplier_adds_a_certificate_only_on_his_own_lots(): void
    {
        $intruder = User::factory()->create(['role' => 'supplier']);
        Supplier::where('name', 'Linificio Nord')->firstOrFail()->update(['user_id' => $intruder->id]);
        $batch = $this->supplier->batches()->firstOrFail();
        $before = $batch->certificates()->count();

        $this->actingAs($intruder)->post(route('supplier.batches.certificate', $batch), ['document' => UploadedFile::fake()->image('grs.jpg')])->assertForbidden();
        $this->get(route('supplier.batches.index'))->assertOk()->assertDontSee($batch->batch_code);

        $this->actingAs($this->supplierAccount)->post(route('supplier.batches.certificate', $batch), ['document' => UploadedFile::fake()->image('grs.jpg')])->assertSessionHasNoErrors();
        $this->assertSame($before + 1, $batch->certificates()->count());
    }

    public function test_demo_story_shows_late_and_pending_orders(): void
    {
        $late = PurchaseOrder::where('notes', 'like', 'Bonnets%')->firstOrFail();
        $this->assertTrue($late->isLate());
        $countered = PurchaseOrder::where('status', 'countered')->firstOrFail();

        $this->actingAs($this->brandUser)->get(route('brand.suppliers.show', $late->supplier))->assertOk()->assertSee('En retard')->assertSee('Votre décision');
        $this->get(route('brand.purchase-orders.show', $countered))->assertOk()->assertSee('Tonte retardée par la météo');
        $this->actingAs($this->supplierAccount)->get(route('supplier.dashboard'))->assertOk()->assertSee('Réponse attendue');
    }
}
