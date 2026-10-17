<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CollectionPoint;
use App\Models\ImpactCategory;
use App\Models\MarketplaceListing;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\Product;
use App\Models\ProductImpactReport;
use App\Models\ProductReturn;
use App\Models\ProductSupplierLink;
use App\Models\RecoveryProgram;
use App\Models\RepairCenter;
use App\Models\RepairOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\TradeInRequest;
use App\Models\User;
use App\Services\ImpactCalculationService;
use Database\Seeders\CircularModulesSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modules Fournisseurs & traçabilité, Impact ESG, Réparation / marketplace.
 */
class CircularModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_traces_a_supplier_batch_up_to_a_conform_product(): void
    {
        [$brandUser, $brand, $product, $cotton] = $this->brandWithProduct();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($brandUser)->post(route('brand.suppliers.store'), [
            'name' => 'Filature Test', 'country' => 'Tunisie', 'status' => 'active',
        ])->assertRedirect();
        $supplier = Supplier::firstOrFail();
        $this->assertSame($brand->id, $supplier->brand_id);

        $this->post(route('brand.purchase-orders.store', $supplier), ['ordered_at' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->post(route('brand.supplier-batches.store', $supplier), [
            'batch_code' => 'LOT-T-1', 'material_id' => $cotton->id, 'quantity_kg' => 100, 'origin_country' => 'Tunisie',
        ])->assertSessionHasNoErrors();
        $batch = SupplierBatch::firstOrFail();
        $this->post(route('brand.certificates.store', $batch), [
            'type' => 'GRS', 'certificate_number' => 'GRS-1', 'issuer' => 'Control Union', 'issued_at' => now()->subMonth()->toDateString(),
        ])->assertSessionHasNoErrors();

        // Avant vérification admin : écart détecté.
        $this->post(route('brand.traceability.store'), ['product_id' => $product->id, 'supplier_batch_id' => $batch->id])->assertSessionHasNoErrors();
        $this->assertSame('mismatch', ProductSupplierLink::firstOrFail()->conformity_status);

        // L'admin vérifie le lot et le certificat, puis contrôle la conformité.
        $this->actingAs($admin)->patch(route('admin.supplier-batches.review', $batch), ['status' => 'verified'])->assertSessionHasNoErrors();
        $this->patch(route('admin.certificates.review', MaterialOriginCertificate::firstOrFail()), ['status' => 'verified'])->assertSessionHasNoErrors();
        $this->post(route('admin.traceability.check', ProductSupplierLink::firstOrFail()))->assertSessionHasNoErrors();

        $this->assertSame('conform', ProductSupplierLink::firstOrFail()->conformity_status);
        $this->assertSame(100, $supplier->fresh()->compliance_score);
        $this->assertDatabaseHas('brand_notifications', ['user_id' => $brandUser->id, 'type' => 'supplier_batch_verified']);
    }

    public function test_supplier_form_shows_server_side_errors_and_keeps_input(): void
    {
        [$brandUser] = $this->brandWithProduct();

        $this->actingAs($brandUser)->from(route('brand.suppliers.create'))
            ->post(route('brand.suppliers.store'), ['name' => '', 'country' => '', 'email' => 'pas-un-email', 'city' => 'Sfax', 'status' => 'active'])
            ->assertRedirect(route('brand.suppliers.create'))
            ->assertSessionHasErrors(['name', 'country', 'email']);

        $this->get(route('brand.suppliers.create'))
            ->assertSee('Le champ raison sociale est obligatoire.')
            ->assertSee('value="Sfax"', false);
    }

    public function test_factories_build_suppliers_with_their_relations(): void
    {
        $supplier = Supplier::factory()
            ->has(SupplierBatch::factory()->count(2)->has(MaterialOriginCertificate::factory()->verified(), 'certificates'), 'batches')
            ->create();

        $this->assertNotNull($supplier->brand);
        $this->assertCount(2, $supplier->batches);
        $this->assertCount(2, $supplier->certificates);
        $this->assertSame($supplier->country, $supplier->batches->first()->origin_country);
    }

    public function test_brand_cannot_access_another_brands_supplier(): void
    {
        [$brandUser] = $this->brandWithProduct();
        $otherBrand = Brand::create(['user_id' => User::factory()->create(['role' => 'brand'])->id, 'name' => 'Autre', 'slug' => 'autre']);
        $supplier = Supplier::create(['brand_id' => $otherBrand->id, 'name' => 'Secret', 'country' => 'France']);

        $this->actingAs($brandUser)->get(route('brand.suppliers.show', $supplier))->assertForbidden();
    }

    public function test_impact_report_is_calculated_validated_and_compared(): void
    {
        [$brandUser, $brand, $product] = $this->brandWithProduct();
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['co2' => 0.4, 'water' => 0.25, 'energy' => 0.2, 'waste' => 0.15] as $code => $weight) {
            ImpactCategory::create(['code' => $code, 'name' => $code, 'unit' => 'u', 'weight' => $weight]);
        }

        $this->actingAs($brandUser)->post(route('brand.impact.store'), ['product_id' => $product->id, 'product_weight_kg' => 0.3])->assertRedirect();
        $report = ProductImpactReport::firstOrFail();
        $this->assertGreaterThan(0, (float) $report->carbon_kg);
        $this->assertCount(4, $report->carbonFootprints);
        $this->assertContains($report->grade, ['A', 'B', 'C', 'D', 'E']);
        $this->get(route('brand.impact.index'))->assertOk()->assertSee($product->name);

        $this->actingAs($admin)->patch(route('admin.impact-reports.validate', $report), ['notes' => 'OK'])->assertSessionHasNoErrors();
        $this->assertSame('validated', $report->fresh()->status);
        $this->get(route('admin.impact-reports.compare', ['products' => [$product->id]]))->assertOk()->assertSee($product->name);
    }

    public function test_circular_product_scores_better_than_conventional_one(): void
    {
        [, , $recycled] = $this->brandWithProduct();
        $leather = Material::create(['name' => 'Cuir test', 'type' => 'animal']);
        $conventional = Product::create(['name' => 'Bottes', 'brand' => 'X', 'sku' => 'B-1', 'category' => 'Chaussures', 'status' => 'published']);
        $conventional->materials()->attach($leather->id, ['percentage' => 100]);
        $service = app(ImpactCalculationService::class);

        $this->assertGreaterThan(
            $service->generate($conventional, null, 1.0)->impact_score,
            $service->generate($recycled, null, 1.0)->impact_score
        );
    }

    public function test_second_life_flow_from_return_to_marketplace_sale(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $consumer = User::factory()->create(['role' => 'consumer']);
        $buyer = User::factory()->create(['role' => 'consumer']);
        [, , $product] = $this->brandWithProduct();
        $program = RecoveryProgram::create(['name' => 'Prog', 'organization_name' => 'Org', 'type' => 'recycling', 'city' => 'Tunis']);
        $point = CollectionPoint::create(['recovery_program_id' => $program->id, 'name' => 'Point', 'address' => 'Rue', 'city' => 'Tunis']);
        $return = ProductReturn::create(['user_id' => $consumer->id, 'product_id' => $product->id, 'collection_point_id' => $point->id, 'reference' => 'RET-T-1', 'status' => 'received', 'condition' => 'wearable']);
        $center = RepairCenter::create(['name' => 'Atelier', 'city' => 'Tunis']);

        $this->actingAs($admin)->post(route('admin.repair-orders.store', $return), ['decision' => 'resale'])->assertRedirect();
        $order = RepairOrder::firstOrFail();
        $this->patch(route('admin.repair-orders.diagnose', $order), ['decision' => 'resale', 'repair_center_id' => $center->id])->assertSessionHasNoErrors();
        $this->post(route('admin.repair-orders.start', $order))->assertSessionHasNoErrors();
        $this->post(route('admin.repair-orders.refurbish', $order), ['actions' => 'Nettoyage', 'grade' => 'A'])->assertSessionHasNoErrors();
        $this->post(route('admin.repair-orders.publish', $order), ['title' => 'Chemise reconditionnée', 'price' => 50])->assertRedirect();

        $listing = MarketplaceListing::firstOrFail();
        $this->assertSame('listed', $order->fresh()->status);
        $this->assertDatabaseHas('reward_transactions', ['user_id' => $consumer->id, 'type' => 'second_life', 'status' => 'validated']);

        $this->actingAs($buyer)->get(route('consumer.marketplace.index'))->assertOk()->assertSee('Chemise reconditionnée');
        $this->post(route('consumer.marketplace.offer', $listing), ['offered_price' => 45])->assertSessionHasNoErrors();

        $offer = $listing->offers()->firstOrFail();
        $this->actingAs($brandOwner = User::find($listing->brand->user_id))->patch(route('brand.marketplace.offers.respond', $offer), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('reserved', $listing->fresh()->status);
        $this->post(route('brand.marketplace.sold', $listing))->assertSessionHasNoErrors();
        $this->assertSame('sold', $listing->fresh()->status);
        $this->assertSame('closed', $order->fresh()->status);
    }

    public function test_trade_in_credits_points_after_reception(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $consumer = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($consumer)->post(route('consumer.trade-ins.store'), ['product_name' => 'Veste', 'condition' => 'good'])->assertSessionHasNoErrors();
        $tradeIn = TradeInRequest::firstOrFail();
        $this->actingAs($admin)->patch(route('admin.trade-ins.evaluate', $tradeIn), ['proposed_points' => 30])->assertSessionHasNoErrors();
        $this->actingAs($consumer)->post(route('consumer.trade-ins.accept', $tradeIn))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reward_transactions', ['user_id' => $consumer->id, 'points' => 30, 'status' => 'pending']);
        $this->actingAs($admin)->post(route('admin.trade-ins.complete', $tradeIn))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reward_transactions', ['user_id' => $consumer->id, 'points' => 30, 'status' => 'validated']);
        $this->assertSame('completed', $tradeIn->fresh()->status);
    }

    public function test_every_module_page_renders_for_its_role_with_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(CircularModulesSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();
        $brandUser = User::where('role', 'brand')->firstOrFail();
        $consumer = User::where('role', 'consumer')->firstOrFail();

        $adminPages = [
            route('admin.suppliers.index'), route('admin.suppliers.show', Supplier::first()), route('admin.traceability.index'),
            route('admin.impact-reports.index'), route('admin.impact-reports.show', ProductImpactReport::first()),
            route('admin.impact-reports.compare', ['products' => Product::pluck('id')->take(3)->all()]),
            route('admin.impact-categories.index'), route('admin.impact-categories.create'), route('admin.impact-metrics.index'),
            route('admin.repair-orders.index'), route('admin.repair-orders.show', RepairOrder::first()), route('admin.repair-centers.index'),
            route('admin.repair-centers.create'), route('admin.marketplace.index'), route('admin.marketplace.show', MarketplaceListing::first()),
            route('admin.trade-ins.index'), route('admin.dashboard'),
        ];
        foreach ($adminPages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $brandPages = [
            route('brand.suppliers.index'), route('brand.suppliers.create'), route('brand.suppliers.show', Supplier::first()),
            route('brand.suppliers.edit', Supplier::first()), route('brand.traceability.index'), route('brand.impact.index'),
            route('brand.impact.show', ProductImpactReport::whereNotNull('brand_id')->first()), route('brand.marketplace.index'),
        ];
        foreach ($brandPages as $url) {
            $this->actingAs($brandUser)->get($url)->assertOk();
        }

        foreach ([route('consumer.marketplace.index'), route('consumer.marketplace.show', MarketplaceListing::first()), route('consumer.repairs.index'), route('consumer.trade-ins.index'), route('consumer.home')] as $url) {
            $this->actingAs($consumer)->get($url)->assertOk();
        }

        // Rôles cloisonnés.
        $this->actingAs($consumer)->get(route('admin.suppliers.index'))->assertForbidden();
        $this->actingAs($brandUser)->get(route('admin.impact-reports.index'))->assertForbidden();
    }

    /** @return array{0: User, 1: Brand, 2: Product, 3: Material} */
    private function brandWithProduct(): array
    {
        $brandUser = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $brandUser->id, 'name' => 'Marque Test', 'slug' => 'marque-test-'.$brandUser->id]);
        $cotton = Material::firstOrCreate(['name' => 'Coton recyclé'], ['type' => 'recycled', 'recyclable' => true]);
        $product = Product::create([
            'brand_id' => $brand->id, 'name' => 'Chemise ReLoop', 'brand' => $brand->name, 'sku' => 'T-'.$brandUser->id,
            'category' => 'Chemise', 'status' => 'published', 'repairable' => true, 'recyclable' => true,
        ]);
        $product->materials()->attach($cotton->id, ['percentage' => 100, 'origin_country' => 'Tunisie', 'certified' => true]);

        return [$brandUser, $brand, $product, $cotton];
    }
}
