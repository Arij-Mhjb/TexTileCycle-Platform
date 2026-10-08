<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Label;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductLabelRecommendation;
use App\Models\User;
use App\Services\ProductLabelAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductLabelRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    private Label $grs;

    protected function setUp(): void
    {
        parent::setUp();
        config(['product_label_ai.key' => null]);
        $this->user = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $this->user->id, 'name' => 'Circular Brand', 'slug' => 'circular-brand', 'is_active' => true]);
        $this->product = Product::create(['brand_id' => $brand->id, 'brand' => $brand->name, 'name' => 'Sweat Transition', 'slug' => 'sweat-transition', 'sku' => 'ST-1', 'category' => 'Sweat', 'status' => 'published', 'is_active' => true]);
        $polyester = Material::firstOrCreate(['name' => 'Polyester recyclé'], ['type' => 'recycled', 'recyclable' => true]);
        $cotton = Material::firstOrCreate(['name' => 'Coton conventionnel'], ['type' => 'plant', 'recyclable' => true]);
        $this->product->materials()->attach([$polyester->id => ['percentage' => 20], $cotton->id => ['percentage' => 80]]);
        $this->grs = Label::create(['name' => 'Global Recycled Standard (GRS)', 'slug' => 'grs', 'pillar' => 'circularity', 'description' => 'Recycled', 'official_url' => 'https://textileexchange.org/', 'is_active' => true]);
        Label::create(['name' => 'Vegan Trademark', 'slug' => 'vegan', 'pillar' => 'animals', 'description' => 'Vegan', 'official_url' => 'https://www.vegansociety.com/', 'is_active' => true]);
    }

    public function test_rules_recommend_transition_and_build_a_product_specific_target(): void
    {
        $results = app(ProductLabelAdvisor::class)->analyse($this->user->brand);
        $grs = $results->firstWhere('label_id', $this->grs->id);

        $this->assertSame('transition', $grs->path);
        $this->assertStringContainsString('20 %', $grs->reason);
        $cottonTarget = collect($grs->target_composition)->firstWhere('name', 'Coton conventionnel');
        $this->assertSame('Coton recyclé', $cottonTarget['target_material']);
        $this->assertEquals(80.0, $cottonTarget['target_percentage']);
        $this->assertNotEmpty($grs->environmental_impacts);
        $this->assertNotEmpty($grs->reputation_impacts);
        $this->assertSame('candidate', $results->firstWhere('label.slug', 'vegan')->path);
    }

    public function test_only_admin_published_products_can_be_analysed(): void
    {
        $this->product->update(['status' => 'review', 'is_active' => false]);

        $this->actingAs($this->user)->post(route('brand.product-label-recommendations.analyse'))
            ->assertSessionHasErrors('products');
        $this->assertDatabaseCount('product_label_recommendations', 0);
        $this->get(route('brand.product-label-recommendations.index'))->assertOk()
            ->assertSee('Aucun produit publié à analyser')
            ->assertSee('En vérification');

        $this->product->update(['status' => 'published', 'is_active' => true]);
        $this->post(route('brand.product-label-recommendations.analyse'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_label_recommendations', ['product_id' => $this->product->id]);
    }

    public function test_brand_can_analyse_select_and_create_a_roadmap_from_a_recommendation(): void
    {
        $this->actingAs($this->user)->post(route('brand.product-label-recommendations.analyse'))->assertRedirect();
        $recommendation = ProductLabelRecommendation::where('label_id', $this->grs->id)->firstOrFail();
        $this->get(route('brand.product-label-recommendations.index'))->assertOk()->assertSee('Sweat Transition')->assertSee('Coton recyclé');
        $this->post(route('brand.product-label-recommendations.assign', $recommendation))->assertRedirect(route('brand.roadmaps.create', [
            'recommendation_id' => $recommendation->id, 'label_id' => $this->grs->id, 'product_id' => $this->product->id,
        ]));
        $this->get(session('url.intended', route('brand.roadmaps.create', ['recommendation_id' => $recommendation->id, 'label_id' => $this->grs->id, 'product_id' => $this->product->id])))
            ->assertOk()->assertSee('Recommandation par règles métier')->assertSee('Sweat Transition');

        $this->post(route('brand.roadmaps.store'), [
            'recommendation_id' => $recommendation->id, 'label_id' => $this->grs->id, 'products' => [$this->product->id],
            'name' => 'Sweat GRS', 'scope_description' => 'Sweat ST-1 et sa chaîne de fabrication',
            'baseline' => $recommendation->reason, 'objective' => 'Remplacer le coton conventionnel par du coton recyclé documenté.',
            'starts_on' => '2026-10-07', 'target_date' => '2027-04-07',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $roadmap = $this->user->brand->roadmaps()->firstOrFail();
        $this->assertSame($recommendation->id, $roadmap->product_label_recommendation_id);
        $this->assertSame('Coton recyclé', collect($roadmap->specification['target_composition'])->firstWhere('name', 'Coton conventionnel')['target_material']);
        $this->assertNotEmpty($roadmap->impact_summary['environmental']);
        $this->assertNotEmpty($roadmap->steps->first()->completion_points);
        $this->get(route('brand.roadmaps.show', $roadmap))->assertOk()->assertSee('Cahier des charges')->assertSee('Réputation de la marque');
        $this->get(route('brand.dashboard'))->assertOk()
            ->assertSee('Labels poursuivis')
            ->assertSee('Global Recycled Standard (GRS)')
            ->assertSee('Impact environnemental potentiel')
            ->assertSee($roadmap->impact_summary['environmental'][0]);
    }

    public function test_openai_can_enrich_explanations_without_changing_the_rule_decision(): void
    {
        config(['product_label_ai.key' => 'test-key', 'product_label_ai.model' => 'test-model']);
        $content = json_encode([
            'recommendations' => [
                ['product_id' => $this->product->id, 'label_id' => $this->grs->id, 'reason' => 'Analyse enrichie GRS', 'gaps' => ['Écart enrichi'], 'next_steps' => ['Action enrichie'], 'environmental_impacts' => ['Impact conditionnel'], 'reputation_impacts' => ['Bénéfice conditionnel']],
                ['product_id' => $this->product->id, 'label_id' => Label::where('slug', 'vegan')->value('id'), 'reason' => 'Analyse enrichie vegan', 'gaps' => ['Vérifier les colles'], 'next_steps' => ['Collecter les déclarations'], 'environmental_impacts' => ['Alternative à évaluer'], 'reputation_impacts' => ['Claim à vérifier']],
            ],
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);

        $result = app(ProductLabelAdvisor::class)->analyse($this->user->brand)->firstWhere('label_id', $this->grs->id);
        $this->assertSame('transition', $result->path);
        $this->assertSame('openai', $result->analysis_mode);
        $this->assertSame('Analyse enrichie GRS', $result->reason);
        $this->assertSame('test-model', $result->analysis_model);
    }

    public function test_existing_compliant_recycled_share_is_not_replaced_unnecessarily(): void
    {
        $linen = Material::firstOrCreate(['name' => 'Lin européen'], ['type' => 'plant', 'recyclable' => true]);
        $recycled = Material::where('name', 'Polyester recyclé')->firstOrFail();
        $this->product->materials()->sync([$recycled->id => ['percentage' => 70], $linen->id => ['percentage' => 30]]);

        $grs = app(ProductLabelAdvisor::class)->analyse($this->user->brand)->firstWhere('label_id', $this->grs->id);
        $linenTarget = collect($grs->target_composition)->firstWhere('name', 'Lin européen');
        $this->assertSame('candidate', $grs->path);
        $this->assertSame('Lin européen', $linenTarget['target_material']);
    }

    public function test_openai_failure_falls_back_to_rule_assessment(): void
    {
        config(['product_label_ai.key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response([], 503)]);

        $grs = app(ProductLabelAdvisor::class)->analyse($this->user->brand)->firstWhere('label_id', $this->grs->id);
        $this->assertSame('rules', $grs->analysis_mode);
        $this->assertSame('transition', $grs->path);
    }

    public function test_brand_cannot_assign_another_brands_recommendation(): void
    {
        $recommendation = app(ProductLabelAdvisor::class)->analyse($this->user->brand)->first();
        $other = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $other->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);

        $this->actingAs($other)->post(route('brand.product-label-recommendations.assign', $recommendation))->assertForbidden();
    }
}
