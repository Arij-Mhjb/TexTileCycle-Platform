<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\CarbonFootprint;
use App\Models\ProductImpactReport;
use App\Services\ImpactCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandImpactController extends Controller
{
    public function index(Request $request, ImpactCalculationService $impact): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return view('modules.esg.brand.index', [
            'brand' => $brand,
            'dashboard' => $impact->brandDashboard($brand),
            'products' => $brand->products()->orderBy('name')->get(['id', 'name', 'category']),
        ]);
    }

    public function store(Request $request, ImpactCalculationService $impact): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'product_id' => 'required|integer',
            'product_weight_kg' => 'nullable|numeric|min:0.05|max:50',
            'season' => 'nullable|string|max:40',
        ]);
        $product = $brand->products()->findOrFail($data['product_id']);
        $report = $impact->generate($product, $request->user(), $data['product_weight_kg'] ?? null, $data['season'] ?? null);

        return to_route('brand.impact.show', $report)->with('success', 'Impact calculé pour « '.$product->name.' » : '.$report->impact_score.'/100.');
    }

    public function show(Request $request, ProductImpactReport $report): View
    {
        abort_unless($report->brand_id === $request->user()->brand?->id, 403);
        $report->load(['product.materials', 'carbonFootprints', 'waterFootprints', 'validator']);

        return view('modules.esg.brand.show', [
            'report' => $report,
            'stages' => CarbonFootprint::STAGES,
            'history' => ProductImpactReport::where('product_id', $report->product_id)->oldest()->get(['id', 'impact_score', 'created_at']),
        ]);
    }
}
