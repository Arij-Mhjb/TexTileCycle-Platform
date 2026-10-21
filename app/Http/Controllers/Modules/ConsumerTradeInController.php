<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TradeInRequest;
use App\Models\CollectionPoint;
use App\Services\MediaStorageService;
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
            'tradeIns' => $request->user()->tradeInRequests()->with(['product', 'collectionPoint.recoveryProgram'])->latest()->get(),
            'products' => Product::where('status', 'published')->orderBy('name')->get(['id', 'name', 'brand']),
            'points' => CollectionPoint::where('is_active', true)->whereHas('recoveryProgram', fn ($q) => $q->where('is_active', true)->where('type', 'take_back'))->with('recoveryProgram')->orderBy('city')->get(),
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
        $data = $request->validate(['collection_point_id' => 'required|exists:collection_points,id']);
        $point = CollectionPoint::whereKey($data['collection_point_id'])->where('is_active', true)
            ->whereHas('recoveryProgram', fn ($q) => $q->where('is_active', true)->where('type', 'take_back'))->first();
        if (! $point) return back()->withErrors(['collection_point_id' => 'Ce point ne prend pas en charge les reprises.']);
        $service->answerTradeIn($tradeIn, true, $point->id);

        return back()->with('success', 'Offre acceptée : déposez l’article dans un point de collecte pour recevoir vos points.');
    }

    public function decline(Request $request, TradeInRequest $tradeIn, RepairMarketplaceService $service): RedirectResponse
    {
        abort_unless($tradeIn->user_id === $request->user()->id, 403);
        $service->answerTradeIn($tradeIn, false);

        return back()->with('success', 'Offre refusée.');
    }

    public function deposit(Request $request, TradeInRequest $tradeIn, MediaStorageService $media): RedirectResponse
    {
        abort_unless($tradeIn->user_id === $request->user()->id && $tradeIn->status === 'accepted', 403);
        $data = $request->validate(['evidence' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192']);
        $stored = $media->storeReturnEvidence($request->file('evidence'), $tradeIn->id, 'trade-in-deposit');
        $evidence = $tradeIn->deposit_evidence ?? [];
        $evidence['consumer'] = $stored;
        $tradeIn->update(['deposit_evidence' => $evidence, 'deposited_at' => now(), 'status' => 'deposited']);

        return back()->with('success', 'Dépôt déclaré. Le collecteur doit maintenant scanner le QR et confirmer la réception.');
    }
}
