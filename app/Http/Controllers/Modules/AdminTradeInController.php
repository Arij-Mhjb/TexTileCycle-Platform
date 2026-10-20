<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\TradeInRequest;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTradeInController extends Controller
{
    public function index(Request $request): View
    {
        return view('modules.repair.admin.trade-ins', [
            'tradeIns' => TradeInRequest::with(['consumer', 'product', 'evaluator'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->orderByRaw("case status when 'submitted' then 0 when 'accepted' then 1 when 'evaluated' then 2 else 3 end")
                ->latest()->paginate(15)->withQueryString(),
            'counts' => TradeInRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function evaluate(Request $request, TradeInRequest $tradeIn, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate(['proposed_points' => 'required|integer|min:1|max:10000', 'evaluation_note' => 'nullable|string|max:1000']);
        $service->evaluateTradeIn($tradeIn, $request->user(), $data['proposed_points'], $data['evaluation_note'] ?? null);

        return back()->with('success', 'Offre de reprise envoyée au consommateur.');
    }

    public function complete(TradeInRequest $tradeIn, RepairMarketplaceService $service): RedirectResponse
    {
        $service->completeTradeIn($tradeIn);

        return back()->with('success', 'Article reçu : points validés dans le wallet du consommateur.');
    }
}
