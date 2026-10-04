<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\CollectionPoint;
use App\Models\Evidence;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\Scan;
use App\Models\TreatmentResult;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $returns = ProductReturn::with(['product', 'collectionPoint'])->get();
        $results = TreatmentResult::all();
        $months = collect(range(5, 0))->map(fn ($offset) => now()->subMonths($offset)->startOfMonth());
        $returnTrend = $months->map(fn (Carbon $month) => [
            'label' => $month->translatedFormat('M Y'),
            'count' => $returns->filter(fn ($return) => $return->created_at->isSameMonth($month))->count(),
            'weight' => round((float) $returns->filter(fn ($return) => $return->created_at->isSameMonth($month))->sum('weight_kg'), 2),
        ]);
        $statusLabels = ['declared' => 'Déclaré', 'received' => 'Reçu', 'sorting' => 'Tri', 'processed' => 'Traité', 'rejected' => 'Refusé'];
        $statusBreakdown = collect($statusLabels)->map(fn ($label, $status) => ['label' => $label, 'value' => $returns->where('status', $status)->count()])->values();
        $programTypes = RecoveryProgram::all()->groupBy('type')->map(fn ($items, $type) => ['label' => str($type)->replace('_', ' ')->title()->toString(), 'value' => $items->count()])->values();
        $scoreAverages = [
            'Environnement' => round((float) Product::whereNotNull('environmental_score')->avg('environmental_score')),
            'Circularité' => round((float) Product::whereNotNull('circularity_score')->avg('circularity_score')),
            'Animal-free' => round((float) Product::whereNotNull('animal_free_score')->avg('animal_free_score')),
            'Traçabilité' => round((float) Product::whereNotNull('traceability_score')->avg('traceability_score')),
        ];

        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::count(), 'materials' => Material::count(), 'programs' => RecoveryProgram::where('is_active', true)->count(),
                'points' => CollectionPoint::where('is_active', true)->count(), 'returns' => $returns->count(), 'weight' => round((float) $returns->sum('weight_kg'), 2),
                'scans' => Scan::count(), 'assessments' => Assessment::count(),
                'recyclingRate' => round((float) $results->avg('recycled_percent'), 1),
                'reuseRate' => round((float) $results->avg('reused_percent'), 1),
            ],
            'charts' => [
                'returnTrend' => $returnTrend,
                'statusBreakdown' => $statusBreakdown,
                'programTypes' => $programTypes,
                'scoreAverages' => $scoreAverages,
            ],
            'recentProducts' => Product::latest()->limit(5)->get(),
            'recentReturns' => ProductReturn::with(['product', 'collectionPoint'])->latest()->limit(5)->get(),
            'expiringEvidences' => Evidence::with('assessment')->whereNotNull('expires_at')->orderBy('expires_at')->limit(5)->get(),
        ]);
    }
}
