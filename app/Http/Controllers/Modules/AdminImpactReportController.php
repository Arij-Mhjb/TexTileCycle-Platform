<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Brand;
use App\Models\CarbonFootprint;
use App\Models\Product;
use App\Models\ProductImpactReport;
use App\Services\ImpactCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminImpactReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = ProductImpactReport::with(['product', 'brand'])
            ->when($request->filled('brand'), fn ($query) => $query->where('brand_id', $request->brand))
            ->when($request->filled('grade'), fn ($query) => $query->where('grade', $request->grade))
            ->latest()->paginate(12)->withQueryString();
        $all = ProductImpactReport::with('brand')->get();

        return view('modules.esg.admin.reports.index', [
            'reports' => $reports,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'reports' => $all->count(),
                'average' => (int) round($all->avg('impact_score') ?? 0),
                'carbon' => $all->sum('carbon_kg'),
                'validated' => $all->where('status', 'validated')->count(),
            ],
            'grades' => collect(['A', 'B', 'C', 'D', 'E'])->mapWithKeys(fn ($grade) => [$grade => $all->where('grade', $grade)->count()]),
            'byBrand' => $all->groupBy(fn ($report) => $report->brand?->name ?? 'Sans marque')->map(fn ($items) => (int) round($items->avg('impact_score'))),
        ]);
    }

    public function store(Request $request, ImpactCalculationService $impact): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_weight_kg' => 'nullable|numeric|min:0.05|max:50',
            'season' => 'nullable|string|max:40',
        ]);
        $report = $impact->generate(Product::findOrFail($data['product_id']), $request->user(), $data['product_weight_kg'] ?? null, $data['season'] ?? null);

        return to_route('admin.impact-reports.show', $report)->with('success', 'Rapport calculé : score '.$report->impact_score.'/100 (classe '.$report->grade.').');
    }

    public function show(ProductImpactReport $report): View
    {
        $report->load(['product.materials', 'brand', 'author', 'validator', 'assessment', 'carbonFootprints', 'waterFootprints']);

        return view('modules.esg.admin.reports.show', [
            'report' => $report,
            'stages' => CarbonFootprint::STAGES,
            'history' => ProductImpactReport::where('product_id', $report->product_id)->oldest()->get(['id', 'impact_score', 'created_at']),
            'assessments' => Assessment::orderByDesc('assessment_date')->get()->mapWithKeys(fn ($assessment) => [$assessment->id => $assessment->organization_name.' · '.$assessment->framework]),
        ]);
    }

    public function validateReport(Request $request, ProductImpactReport $report, ImpactCalculationService $impact): RedirectResponse
    {
        $data = $request->validate([
            'assessment_id' => 'nullable|exists:assessments,id',
            'notes' => 'nullable|string|max:2000',
        ]);
        $impact->validate($report, $request->user(), $data['assessment_id'] ?? null, $data['notes'] ?? null);

        return back()->with('success', 'Rapport validé et marque notifiée.');
    }

    public function compare(Request $request): View
    {
        $ids = collect($request->input('products', []))->map(fn ($id) => (int) $id)->filter()->take(5);
        $latest = ProductImpactReport::with('product')->whereIn('product_id', $ids)->latest()->get()->unique('product_id')->values();

        return view('modules.esg.admin.reports.compare', [
            'products' => Product::whereHas('impactReports')->orderBy('name')->get(['id', 'name', 'category']),
            'selected' => $ids,
            'reports' => $latest,
        ]);
    }
}
