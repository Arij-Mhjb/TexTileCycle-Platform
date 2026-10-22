<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ProductImpactReport;
use App\Models\RepairOrder;
use App\Models\Supplier;
use App\Services\Ai\ImpactAdvisorService;
use App\Services\Ai\RepairAiService;
use App\Services\Ai\SupplierAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fonctionnalités IA des 3 modules (Gemini, avec repli local si l'API est indisponible).
 */
class ModuleAiController extends Controller
{
    // ---------- Module 1 : Fournisseurs ----------

    public function brandSupplierRisk(Request $request, Supplier $supplier, SupplierAiService $ai): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
        $result = $ai->analyseRisk($supplier);

        return back()->with('success', 'Analyse IA terminée : risque '.$result['level'].'.');
    }

    public function adminSupplierRisk(Supplier $supplier, SupplierAiService $ai): RedirectResponse
    {
        $result = $ai->analyseRisk($supplier);

        return back()->with('success', 'Analyse IA terminée : risque '.$result['level'].'.');
    }

    // ---------- Module 2 : Impact ESG ----------

    public function brandRecommendations(Request $request, ProductImpactReport $report, ImpactAdvisorService $ai): RedirectResponse
    {
        abort_unless($report->brand_id === $request->user()->brand?->id, 403);
        $result = $ai->recommend($report);

        return back()->with('success', 'Recommandations IA générées : jusqu’à '.$result['best_score'].'/100 possible.');
    }

    public function adminRecommendations(ProductImpactReport $report, ImpactAdvisorService $ai): RedirectResponse
    {
        $result = $ai->recommend($report);

        return back()->with('success', 'Recommandations IA générées : jusqu’à '.$result['best_score'].'/100 possible.');
    }

    // ---------- Module 3 : Seconde vie ----------

    public function adminRepairDiagnosis(Request $request, RepairOrder $repairOrder, RepairAiService $ai): RedirectResponse
    {
        $request->validate(['photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192'], [], ['photo' => 'photo de l’article']);
        $result = $ai->diagnose($repairOrder, $request->file('photo')?->getRealPath());

        return back()->with('success', 'Diagnostic IA : '.(RepairOrder::DECISIONS[$result['decision']] ?? $result['decision']).', grade '.$result['grade'].'.');
    }
}
