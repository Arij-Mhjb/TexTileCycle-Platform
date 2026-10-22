<?php

namespace Tests\Feature;

use App\Models\BrandNotification;
use App\Models\Material;
use App\Models\RecyclingLoop;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Boucle de recyclage marque ↔ fournisseur : la marque confie pièces défectueuses et surplus,
 * le fournisseur recycle ; la marque récupère la matière (elle paie les frais, calculés automatiquement)
 * ou le fournisseur la rachète (il verse un avoir).
 */
class RecyclingLoopTest extends TestCase
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
        config(['huggingface.token' => 'hf_test', 'services.gemini.key' => null]);
        RecyclingLoop::query()->delete();

        $this->brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $this->supplierUser = User::where('email', 'fournisseur@textilecycle.test')->firstOrFail();
        $this->supplier = Supplier::where('user_id', $this->supplierUser->id)->firstOrFail();
    }

    private function material(string $name): int
    {
        return Material::where('name', $name)->value('id');
    }

    private function request(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->brandUser)->post(route('brand.recycling.store'), array_replace_recursive([
            'supplier_id' => $this->supplier->id,
            'mode' => 'recover',
            'brand_message' => 'Nous voulons récupérer du fil recyclé.',
            'items' => [
                ['kind' => 'defective', 'material_id' => $this->material('Coton conventionnel'), 'pieces' => 20, 'quantity_kg' => 14, 'issue' => 'Couture décalée', 'photo' => UploadedFile::fake()->image('jean.jpg')],
                ['kind' => 'surplus', 'material_id' => $this->material('Coton conventionnel'), 'quantity_kg' => 26],
            ],
        ], $overrides));
    }

    public function test_full_loop_from_brand_request_to_recycled_lot_back_at_the_brand(): void
    {
        Http::fake(['router.huggingface.co/*' => Http::response([
            ['label' => 'a photo of fabric with a hole or a tear', 'score' => 0.52],
            ['label' => 'a photo of clean fabric rolls in perfect condition', 'score' => 0.05],
        ])]);

        // 1. La marque confie 40 kg (pièces défectueuses + surplus). L'IA analyse la photo.
        $this->request()->assertRedirect();
        $loop = RecyclingLoop::with('items')->firstOrFail();
        $this->assertSame('requested', $loop->status);
        $this->assertSame(40.0, $loop->totalKg());
        // Nom du lot généré automatiquement (nature · matière · pièces).
        $this->assertSame('Défectueux · Coton conventionnel · 20 pièces', $loop->items[0]->label);
        $this->assertSame('Surplus · Coton conventionnel', $loop->items[1]->label);
        $this->assertSame('huggingface', $loop->items[0]->ai_analysis['source']);
        $this->assertTrue($loop->items[0]->ai_analysis['consistent']);
        $this->assertNull($loop->items[1]->ai_analysis);
        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Authorization', 'Bearer hf_test'));
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $this->supplierUser->id, 'type' => 'recycling_requested']);

        // Le prix est estimé dès la demande : collecte 15 + 0,10 × 40 = 19 ; démontage 20 × 0,25 = 5 ; recyclage 40 × 1,20 = 48.
        $this->assertSame('estimate', $loop->pricing['stage']);
        $this->assertSame(72.0, $loop->amount());

        // 2. Le fournisseur voit l'estimation (coton → coton recyclé) et confirme ce qu'il peut produire. Il ne saisit aucun prix.
        $this->actingAs($this->supplierUser)->get(route('supplier.recycling.show', $loop))->assertOk()->assertSee('Coton recyclé')->assertSee('Confirmer ce que je peux produire');
        $this->post(route('supplier.recycling.propose', $loop), ['output_material_id' => $this->material('Coton recyclé'), 'expected_output_kg' => 45])
            ->assertSessionHasErrors('expected_output_kg');
        $this->post(route('supplier.recycling.propose', $loop), ['output_material_id' => $this->material('Coton recyclé'), 'expected_output_kg' => 32, 'unit_price' => 999])
            ->assertSessionHasNoErrors();
        $loop->refresh();
        $this->assertSame('proposal', $loop->pricing['stage']);
        $this->assertSame(72.0, $loop->amount());
        $this->assertEquals(216, $loop->pricing['savings']); // 32 kg × 9 TND (coton neuf) − 72 TND de frais
        $this->actingAs($this->brandUser)->get(route('brand.recycling.show', $loop))->assertOk()->assertSee('72,00 TND')->assertSee('Accepter la proposition');

        // 3. La marque accepte et expédie.
        $this->actingAs($this->brandUser)->post(route('brand.recycling.ship', $loop))->assertSessionHasErrors('status');
        $this->post(route('brand.recycling.accept', $loop))->assertSessionHasNoErrors();
        $this->post(route('brand.recycling.ship', $loop))->assertSessionHasNoErrors();

        // 4. Le fournisseur pèse (± 10 %), recycle (pas plus que reçu) avec preuve, puis livre.
        $this->actingAs($this->supplierUser)->post(route('supplier.recycling.receive', $loop), ['received_kg' => 30])->assertSessionHasErrors('received_kg');
        $this->post(route('supplier.recycling.receive', $loop), ['received_kg' => 39.2])->assertSessionHasNoErrors();
        $proof = UploadedFile::fake()->create('bon-production.pdf', 30, 'application/pdf');
        $this->post(route('supplier.recycling.transform', $loop), ['output_kg' => 41, 'proof' => $proof])->assertSessionHasErrors('output_kg');
        $this->post(route('supplier.recycling.transform', $loop), ['output_kg' => 31.4, 'proof' => $proof])->assertSessionHasNoErrors();
        $this->assertSame(80, $loop->fresh()->yieldRate());
        // Prix définitif sur le poids réellement pesé : 15 + 3,92 + 5 + 39,2 × 1,20 = 70,96 TND.
        $this->assertTrue($loop->fresh()->priceIsFinal());
        $this->assertSame(70.96, $loop->fresh()->amount());
        $this->post(route('supplier.recycling.deliver', $loop))->assertSessionHasNoErrors();

        $loop->refresh();
        $batch = SupplierBatch::findOrFail($loop->output_batch_id);
        $this->assertSame($this->supplier->id, $batch->supplier_id);
        $this->assertSame($this->material('Coton recyclé'), $batch->material_id);
        $this->assertEquals(31.4, (float) $batch->quantity_kg);
        $this->assertNull($batch->received_at);

        // 5. La marque reçoit : la boucle est fermée et le lot rejoint ses lots reçus.
        $this->actingAs($this->brandUser)->post(route('brand.recycling.close', $loop))->assertSessionHasNoErrors();
        $this->assertSame('closed', $loop->fresh()->status);
        $this->assertNotNull($batch->fresh()->received_at);
        $this->get(route('brand.recycling.show', $loop))->assertOk()->assertSee('Terminé')->assertSee($batch->batch_code);
        $this->assertTrue(BrandNotification::where('user_id', $this->supplierUser->id)->where('type', 'recycling_closed')->exists());
        $this->assertSame(7, BrandNotification::whereIn('type', ['recycling_requested', 'recycling_proposed', 'recycling_accepted', 'recycling_shipped', 'recycling_received', 'recycling_transformed', 'recycling_delivered'])->count());
    }

    public function test_buyback_gives_the_brand_a_credit_and_creates_no_lot(): void
    {
        Http::fake();
        $this->request(['mode' => 'buyback', 'items' => [0 => ['photo' => null]]])->assertSessionHasNoErrors();
        $loop = RecyclingLoop::firstOrFail();
        $this->assertSame('buyback', $loop->mode);

        $this->actingAs($this->supplierUser)->post(route('supplier.recycling.propose', $loop), ['output_material_id' => $this->material('Coton recyclé'), 'expected_output_kg' => 32]);
        // 32 kg × 6,50 TND × 60 % = 124,80 TND de rachat − 72 TND de frais = 52,80 TND d'avoir pour la marque.
        $this->assertSame(-52.8, $loop->fresh()->amount());
        $this->assertEquals(52.8, $loop->fresh()->pricing['credit']);

        $this->actingAs($this->brandUser)->post(route('brand.recycling.accept', $loop));
        $this->post(route('brand.recycling.ship', $loop));
        $this->actingAs($this->supplierUser)->post(route('supplier.recycling.receive', $loop), ['received_kg' => 40]);
        $this->post(route('supplier.recycling.transform', $loop), ['output_kg' => 32, 'proof' => UploadedFile::fake()->image('balles.jpg')]);
        $this->get(route('supplier.recycling.show', $loop))->assertOk()->assertSee('Verser l’avoir');
        $this->post(route('supplier.recycling.deliver', $loop))->assertSessionHasNoErrors();
        $this->assertNull($loop->fresh()->output_batch_id);
        $this->assertSame(0, SupplierBatch::where('quantity_kg', 32)->count());
        $this->assertTrue(BrandNotification::where('user_id', $this->brandUser->id)->where('type', 'recycling_delivered')->where('title', 'like', '%avoir%')->exists());

        $this->actingAs($this->brandUser)->post(route('brand.recycling.close', $loop))->assertSessionHasNoErrors();
        $this->assertSame('closed', $loop->fresh()->status);
    }

    public function test_request_validation_and_supplier_can_decline(): void
    {
        Http::fake();

        $this->request(['items' => [0 => ['issue' => '', 'photo' => null]]])->assertSessionHasErrors('items.0.issue');
        $this->request(['items' => [0 => ['quantity_kg' => 0]]])->assertSessionHasErrors('items.0.quantity_kg');
        $other = Supplier::factory()->create();
        $this->request(['supplier_id' => $other->id])->assertSessionHasErrors('supplier_id');
        $this->request(['mode' => 'gratuit'])->assertSessionHasErrors('mode');

        $this->request(['items' => [0 => ['photo' => null]]])->assertSessionHasNoErrors();
        $loop = RecyclingLoop::firstOrFail();
        $this->actingAs($this->supplierUser)->post(route('supplier.recycling.decline', $loop), ['decline_reason' => 'Nous ne recyclons pas cette matière.'])->assertSessionHasNoErrors();
        $this->assertSame('declined', $loop->fresh()->status);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $this->brandUser->id, 'type' => 'recycling_declined']);
    }

    public function test_pages_and_isolation(): void
    {
        Http::fake();
        $this->actingAs($this->brandUser)->get(route('brand.recycling.create'))->assertOk()->assertSee($this->supplier->name)->assertSee('Que voulez-vous en retour');
        $this->request(['items' => [0 => ['photo' => null]]]);
        $loop = RecyclingLoop::firstOrFail();

        $this->get(route('brand.recycling.index'))->assertOk()->assertSee($this->supplier->name)->assertSee('Chez l’autre partie');
        $this->actingAs($this->supplierUser)->get(route('supplier.recycling.index'))->assertOk()->assertSee('À faire maintenant')->assertSee('Confirmer ce que je peux produire');
        $this->get(route('supplier.dashboard'))->assertOk()->assertSee('Recyclage')->assertSee('Confirmer ce que je peux produire');
        $this->actingAs(User::where('email', 'admin@textilecycle.test')->firstOrFail())->get(route('admin.recycling.show', $loop))->assertOk()->assertDontSee('Envoyer ma proposition');
        $this->get(route('admin.recycling.index'))->assertOk()->assertSee('Flux de matière')->assertSee($this->supplier->name);

        $stranger = User::factory()->create(['role' => 'supplier', 'must_change_password' => false]);
        $this->actingAs($stranger)->get(route('supplier.recycling.show', $loop))->assertForbidden();
        $this->post(route('supplier.recycling.propose', $loop), ['output_material_id' => 1, 'expected_output_kg' => 1])->assertForbidden();
        $this->actingAs(User::where('email', 'consumer@textilecycle.test')->firstOrFail())->get(route('brand.recycling.index'))->assertForbidden();
    }
}
