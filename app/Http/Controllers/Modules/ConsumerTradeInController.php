<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TradeInRequest;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsumerTradeInController extends Controller
{
    public function index(Request $request): View
    {
        return view('modules.repair.consumer.trade-ins', [
            'tradeIns' => $request->user()->tradeInRequests()->with('product')->latest()->get(),
            'products' => Product::where('status', 'published')->orderBy('name')->get(['id', 'name', 'brand']),
        ]);
    }

    public function store(Request $request, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'nullable|required_without:product_name|exists:products,id',
            'product_name' => 'nullable|required_without:product_id|string|max:150',
            'condition' => ['required', Rule::in(array_keys(TradeInRequest::CONDITIONS))],
            'description' => 'nullable|string|max:1000',
        ]);
        $tradeIn = $service->submitTradeIn($request->user(), $data);

        return back()->with('success', 'Demande de reprise '.$tradeIn->reference.' envoyée.');
    }

    public function accept(Request $request, TradeInRequest $tradeIn, RepairMarketplaceService $service): RedirectResponse
    {
        abort_unless($tradeIn->user_id === $request->user()->id, 403);
        $service->answerTradeIn($tradeIn, true);

        return back()->with('success', 'Offre acceptée : déposez l’article dans un point de collecte pour recevoir vos points.');
    }

    public function decline(Request $request, TradeInRequest $tradeIn, RepairMarketplaceService $service): RedirectResponse
    {
        abort_unless($tradeIn->user_id === $request->user()->id, 403);
        $service->answerTradeIn($tradeIn, false);

        return back()->with('success', 'Offre refusée.');
    }
}
