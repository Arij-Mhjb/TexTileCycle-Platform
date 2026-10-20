<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerMarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        return view('modules.repair.consumer.marketplace', [
            'listings' => MarketplaceListing::with(['product', 'brand'])->where('status', 'published')
                ->when($request->filled('grade'), fn ($query) => $query->where('condition_grade', $request->grade))
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->search.'%'))
                ->latest('published_at')->paginate(12)->withQueryString(),
            'myOffers' => $request->user()->resaleOffers()->with('listing')->latest()->take(5)->get(),
        ]);
    }

    public function show(Request $request, MarketplaceListing $listing): View
    {
        abort_unless(in_array($listing->status, ['published', 'reserved', 'sold'], true) || $listing->repairOrder?->user_id === $request->user()->id, 404);
        $listing->load(['product.materials', 'brand', 'repairOrder.refurbishments']);

        return view('modules.repair.consumer.listing', [
            'listing' => $listing,
            'myOffer' => $listing->offers()->where('user_id', $request->user()->id)->latest()->first(),
        ]);
    }

    public function offer(Request $request, MarketplaceListing $listing, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'offered_price' => 'required|numeric|min:1|max:'.max(1, (float) $listing->price * 2),
            'message' => 'nullable|string|max:500',
        ]);
        $service->makeOffer($listing, $request->user(), (float) $data['offered_price'], $data['message'] ?? null);

        return back()->with('success', 'Offre envoyée. Vous serez notifié de la réponse.');
    }
}
