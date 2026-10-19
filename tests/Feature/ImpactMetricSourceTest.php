<?php

namespace Tests\Feature;

use App\Models\ImpactCategory;
use App\Models\Material;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** La source d'un facteur d'impact se choisit dans une liste de bases ACV, ou « Autre » à préciser. */
class ImpactMetricSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_is_chosen_from_the_list_or_specified_as_other(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();
        $material = Material::where('name', 'Laine recyclée')->firstOrFail();
        $co2 = ImpactCategory::where('code', 'co2')->firstOrFail();
        $payload = ['material_id' => $material->id, 'impact_category_id' => $co2->id, 'value_per_kg' => 2.5];

        $this->actingAs($admin)->get(route('admin.impact-metrics.index'))->assertOk()->assertSee('Base Empreinte (ADEME)')->assertSee('Autre…', false);

        $this->post(route('admin.impact-metrics.store'), $payload + ['source' => 'Base Empreinte (ADEME)'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('impact_metrics', ['material_id' => $material->id, 'impact_category_id' => $co2->id, 'source' => 'Base Empreinte (ADEME)']);

        $this->post(route('admin.impact-metrics.store'), $payload + ['source' => 'n’importe quoi'])->assertSessionHasErrors('source');
        $this->post(route('admin.impact-metrics.store'), $payload + ['source' => 'other'])->assertSessionHasErrors('source_other');
        $this->post(route('admin.impact-metrics.store'), $payload + ['source' => 'other', 'source_other' => 'Étude interne 2026'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('impact_metrics', ['material_id' => $material->id, 'impact_category_id' => $co2->id, 'source' => 'Étude interne 2026']);
    }
}
