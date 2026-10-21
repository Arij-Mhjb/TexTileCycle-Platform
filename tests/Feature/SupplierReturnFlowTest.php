<?php

namespace Tests\Feature;

use App\Models\BrandNotification;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\SupplierReturn;
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
 * Seconde vie de la matière entre marque et fournisseur :
 * déclaration (photo analysée par CLIP), reprise, renvoi, réception, seconde vie avec preuve, vérification.
 */
class SupplierReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $brandUser;

    private User $supplierUser;

    private User $admin;

    private SupplierBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['huggingface.token' => 'hf_test', 'services.gemini.key' => null]);

        $this->brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $this->supplierUser = User::where('email', 'fournisseur@textilecycle.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@textilecycle.test')->firstOrFail();
        $supplier = Supplier::where('user_id', $this->supplierUser->id)->firstOrFail();
        $this->batch = SupplierBatch::factory()->create([
            'supplier_id' => $supplier->id, 'quantity_kg' => 200, 'received_at' => now()->subDays(3), 'status' => 'received',
        ]);
        SupplierReturn::query()->delete();
    }

    private function fakeClip(): void
    {
        Http::fake(['router.huggingface.co/*' => Http::response([
            ['label' => 'a photo of fabric with a stain', 'score' => 0.58],
            ['label' => 'a photo of faded discolored fabric', 'score' => 0.21],
            ['label' => 'a photo of clean fabric rolls in perfect condition', 'score' => 0.04],
        ])]);
    }

    private function declare(array $data = []): TestResponse
    {
        return $this->actingAs($this->brandUser)->post(route('brand.supplier-returns.store'), $data + [
            'supplier_batch_id' => $this->batch->id, 'quantity_kg' => 50, 'reason' => 'defect',
            'description' => 'Taches d’huile sur un rouleau', 'photo' => UploadedFile::fake()->image('defaut.jpg'),
        ]);
    }

    public function test_full_second_life_story_from_brand_to_supplier_to_admin(): void
    {
        $this->fakeClip();

        // 1. La marque déclare un défaut avec photo : CLIP voit une tache, le motif est confirmé.
        $this->declare()->assertRedirect();
        $return = SupplierReturn::firstOrFail();
        $this->assertSame('requested', $return->status);
        $this->assertSame('huggingface', $return->ai_analysis['source']);
        $this->assertSame('Tache', $return->ai_analysis['findings'][0]['label']);
        $this->assertTrue($return->ai_analysis['consistent']);
        Storage::disk('local')->assertExists($return->photo_path);
        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Authorization', 'Bearer hf_test') && isset($request->data()['parameters']['candidate_labels']));
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $this->supplierUser->id, 'type' => 'supplier_return_requested']);

        // 2. Le fournisseur voit le retour, l'analyse IA, et accepte la reprise pour recyclage.
        $this->actingAs($this->supplierUser)->get(route('supplier.returns.show', $return))->assertOk()->assertSee('La photo confirme le motif')->assertSee('Reprendre la matière');
        $this->post(route('supplier.returns.accept', $return), ['destination' => 'recycling', 'pickup_at' => now()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $return->fresh()->status);

        // 3. La marque renvoie, le fournisseur confirme la réception.
        $this->post(route('supplier.returns.receive', $return))->assertSessionHasErrors('status');
        $this->actingAs($this->brandUser)->post(route('brand.supplier-returns.ship', $return))->assertSessionHasNoErrors();
        $this->actingAs($this->supplierUser)->post(route('supplier.returns.receive', $return))->assertSessionHasNoErrors();

        // 4. Seconde vie : le total doit correspondre aux kg reçus (± 5 %), une preuve est obligatoire.
        $this->post(route('supplier.returns.complete', $return), ['resold_kg' => 0, 'recycled_kg' => 20, 'upcycled_kg' => 0, 'waste_kg' => 0, 'proof' => UploadedFile::fake()->create('bon.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('resold_kg');
        $this->post(route('supplier.returns.complete', $return), ['resold_kg' => 0, 'recycled_kg' => 44, 'upcycled_kg' => 5, 'waste_kg' => 1, 'proof' => UploadedFile::fake()->create('bon.pdf', 20, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $return->refresh();
        $this->assertSame('completed', $return->status);
        $this->assertSame(49.0, $return->savedKg());
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $this->admin->id, 'type' => 'supplier_return_completed']);

        // 5. L'admin demande une correction (note obligatoire), puis valide après correction.
        $this->actingAs($this->admin)->patch(route('admin.supplier-returns.verify', $return), ['decision' => 'correction'])->assertSessionHasErrors('admin_note');
        $this->patch(route('admin.supplier-returns.verify', $return), ['decision' => 'correction', 'admin_note' => 'Le bon ne mentionne pas le poids.'])->assertSessionHasNoErrors();
        $this->assertSame('correction', $return->fresh()->status);
        $this->actingAs($this->supplierUser)->post(route('supplier.returns.complete', $return), ['resold_kg' => 0, 'recycled_kg' => 45, 'upcycled_kg' => 5, 'waste_kg' => 0, 'proof' => UploadedFile::fake()->image('bon-pese.jpg')])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('admin.supplier-returns.verify', $return), ['decision' => 'approve'])->assertSessionHasNoErrors();

        $return->refresh();
        $this->assertSame('verified', $return->status);
        $this->assertSame($this->admin->id, $return->verified_by);
        $this->get(route('admin.supplier-returns.index'))->assertOk()->assertSee($return->reference)->assertSee('50 kg');
        $this->assertTrue(BrandNotification::where('user_id', $this->brandUser->id)->where('type', 'supplier_return_verified')->exists());
    }

    public function test_quantity_cannot_exceed_what_was_received_and_defects_need_proof(): void
    {
        $this->fakeClip();

        $this->declare(['quantity_kg' => 250])->assertSessionHasErrors('quantity_kg');
        $this->declare(['photo' => null, 'description' => null])->assertSessionHasErrors(['photo', 'description']);
        $this->declare(['quantity_kg' => 150])->assertSessionHasNoErrors();
        // Il ne reste que 50 kg renvoyables sur ce lot.
        $this->declare(['quantity_kg' => 60, 'reason' => 'surplus', 'photo' => null])->assertSessionHasErrors('quantity_kg');
        $this->actingAs($this->brandUser)->get(route('brand.supplier-returns.create', ['batch' => $this->batch->id]))->assertOk()->assertSee('maximum 50,00 kg');

        $pending = SupplierBatch::factory()->create(['supplier_id' => $this->batch->supplier_id, 'received_at' => null, 'status' => 'pending']);
        $this->declare(['supplier_batch_id' => $pending->id])->assertSessionHasErrors('quantity_kg');
    }

    public function test_surplus_without_photo_works_and_the_ai_flags_an_unconvincing_defect(): void
    {
        Http::fake(['router.huggingface.co/*' => Http::response([
            ['label' => 'a photo of clean fabric rolls in perfect condition', 'score' => 0.74],
            ['label' => 'a photo of fabric with a stain', 'score' => 0.08],
        ])]);

        $this->declare(['reason' => 'surplus', 'photo' => null, 'description' => null])->assertSessionHasNoErrors();
        $this->assertNull(SupplierReturn::latest('id')->first()->ai_analysis);

        $this->declare(['quantity_kg' => 20])->assertSessionHasNoErrors();
        $analysis = SupplierReturn::latest('id')->first()->ai_analysis;
        $this->assertFalse($analysis['defect_visible']);
        $this->assertFalse($analysis['consistent']);
    }

    public function test_each_actor_only_sees_the_returns_that_concern_him(): void
    {
        Http::fake();
        $this->declare(['reason' => 'surplus', 'photo' => null, 'description' => null]);
        $return = SupplierReturn::firstOrFail();

        $otherSupplierUser = User::factory()->create(['role' => 'supplier', 'must_change_password' => false]);
        $this->actingAs($otherSupplierUser)->get(route('supplier.returns.show', $return))->assertForbidden();
        $this->post(route('supplier.returns.accept', $return), ['destination' => 'resale'])->assertForbidden();

        $consumer = User::where('email', 'consumer@textilecycle.test')->firstOrFail();
        $this->actingAs($consumer)->get(route('brand.supplier-returns.index'))->assertForbidden();

        $this->actingAs($this->supplierUser)->get(route('supplier.returns.index'))->assertOk()->assertSee($return->reference);
        $this->actingAs($this->brandUser)->get(route('brand.supplier-returns.index'))->assertOk()->assertSee($return->reference);
    }
}
