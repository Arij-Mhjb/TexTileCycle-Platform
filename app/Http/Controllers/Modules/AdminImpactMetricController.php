<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ImpactCategory;
use App\Models\ImpactMetric;
use App\Models\Material;
use App\Services\ImpactCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminImpactMetricController extends Controller
{
    public function index(): View
    {
        $categories = ImpactCategory::orderBy('id')->get();

        return view('modules.esg.admin.metrics', [
            'categories' => $categories,
            'materials' => Material::with('impactMetrics')->orderBy('name')->get(),
            'defaults' => ImpactCalculationService::DEFAULT_FACTORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'impact_category_id' => 'required|exists:impact_categories,id',
            'value_per_kg' => 'required|numeric|min:0|max:100000',
            'source' => 'nullable|string|max:255',
        ]);

        ImpactMetric::updateOrCreate(
            ['material_id' => $data['material_id'], 'impact_category_id' => $data['impact_category_id']],
            ['value_per_kg' => $data['value_per_kg'], 'source' => $data['source'] ?? null]
        );

        return back()->with('success', 'Facteur d’impact enregistré.');
    }

    public function destroy(ImpactMetric $metric): RedirectResponse
    {
        $metric->delete();

        return back()->with('success', 'Facteur supprimé : la valeur par défaut du type de matière sera utilisée.');
    }
}
