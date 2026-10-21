<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Material;
use App\Models\ProductImpactReport;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\SupplierScorecardService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Simulateur ESG « et si ? » et performance fournisseurs (OTIF).
 */
class SmartModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_impact_simulator_recalculates_without_saving_and_is_scoped_to_the_brand(): void
    {
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $report = ProductImpactReport::where('brand_id', $brandUser->brand->id)->firstOrFail();
        $virgin = Material::firstOrCreate(['name' => 'Polyester vierge'], ['type' => 'synthetic']);
        $recycled = Material::firstOrCreate(['name' => 'Polyester recyclé'], ['type' => 'recycled']);
        $reports = ProductImpactReport::count();
        $payload = fn (Material $material, bool $flags) => [
            'weight_kg' => 0.5, 'composition' => [['material_id' => $material->id, 'percentage' => 100]],
            'repairable' => $flags, 'reusable' => $flags, 'recyclable' => $flags,
        ];

        $worse = $this->actingAs($brandUser)->postJson(route('brand.impact.simulate', $report), $payload($virgin, false))->assertOk()->json();
        $better = $this->postJson(route('brand.impact.simulate', $report), $payload($recycled, true))->assertOk()->json();

        $this->assertGreaterThan($worse['score'], $better['score']);
        $this->assertLessThan($worse['totals']['co2'], $better['totals']['co2']);
        $this->assertSame($better['score'] - $better['baseline'], $better['delta']);
        $this->assertSame($reports, ProductImpactReport::count());
        $this->get(route('brand.impact.show', $report))->assertOk()->assertSee('Et si on changeait la composition');

        $this->postJson(route('brand.impact.simulate', $report), ['weight_kg' => 0, 'composition' => []])->assertUnprocessable();
        $otherBrand = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $otherBrand->id, 'name' => 'Autre marque', 'slug' => 'autre-marque']);
        $this->actingAs($otherBrand)->postJson(route('brand.impact.simulate', $report), $payload($recycled, true))->assertForbidden();
    }

    public function test_supplier_scorecard_measures_on_time_in_full_deliveries(): void
    {
        $brandUser = User::where('email', 'brand@textilecycle.test')->firstOrFail();
        $supplier = Supplier::factory()->create(['brand_id' => $brandUser->brand->id, 'name' => 'Filature Test OTIF', 'compliance_score' => 80]);
        $material = Material::firstOrFail();

        // Commande 1 : 100 kg attendus le 10, reçus en totalité le 8 → OTIF.
        // Commande 2 : 100 kg attendus le 10, 60 kg reçus le 15 puis soldée → ni à l'heure ni complète.
        foreach ([[100, '2026-09-08', false], [60, '2026-09-15', true]] as $i => [$kg, $receivedAt, $short]) {
            $order = PurchaseOrder::create([
                'brand_id' => $supplier->brand_id, 'supplier_id' => $supplier->id, 'reference' => 'PO-OTIF-'.$i, 'status' => 'received',
                'ordered_at' => '2026-08-20', 'expected_at' => '2026-09-10', 'sent_at' => '2026-08-20 09:00:00', 'accepted_at' => '2026-08-20 21:00:00',
                'closed_short_at' => $short ? '2026-09-16 10:00:00' : null,
            ]);
            $line = $order->lines()->create(['material_id' => $material->id, 'quantity_kg' => 100]);
            SupplierBatch::create([
                'supplier_id' => $supplier->id, 'purchase_order_id' => $order->id, 'purchase_order_line_id' => $line->id, 'material_id' => $material->id,
                'batch_code' => 'OTIF-'.$i, 'quantity_kg' => $kg, 'origin_country' => 'Tunisie', 'received_at' => $receivedAt, 'status' => 'received',
            ]);
        }

        $card = app(SupplierScorecardService::class)->card($supplier->fresh());

        $this->assertSame(2, $card['evaluated']);
        $this->assertEquals(50, $card['otif']);
        $this->assertSame(50, $card['on_time']);
        $this->assertSame(50, $card['in_full']);
        $this->assertSame(80, $card['fill_rate']);
        $this->assertSame(5.0, $card['avg_delay_days']);
        $this->assertSame(12.0, $card['avg_reply_hours']);
        $this->assertSame(100, $card['responsiveness']);
        $this->assertSame(72, $card['score']); // 0,4×50 + 0,2×80 + 0,2×80 + 0,2×100 = 20 + 16 + 16 + 20
        $this->assertSame('B', $card['grade']);

        $this->actingAs($brandUser)->get(route('brand.supplier-performance'))->assertOk()->assertSee('Filature Test OTIF')->assertSee('Performance des fournisseurs');
        $this->actingAs(User::where('email', 'admin@textilecycle.test')->firstOrFail())->get(route('admin.supplier-performance'))->assertOk()->assertSee('Filature Test OTIF');
    }
}
