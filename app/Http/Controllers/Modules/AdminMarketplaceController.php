<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\ResaleOffer;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        return view('modules.repair.admin.marketplace.index', [
            'listings' => MarketplaceListing::with(['product', 'brand'])->withCount(['offers', 'offers as pending_offers_count' => fn ($query) => $query->where('status', 'pending')])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->latest()->paginate(12)->withQueryString(),
            'stats' => [
                'published' => MarketplaceListing::where('status', 'published')->count(),
                'reserved' => MarketplaceListing::where('status', 'reserved')->count(),
                'sold' => MarketplaceListing::where('status', 'sold')->count(),
                'revenue' => (float) MarketplaceListing::where('status', 'sold')->sum('price'),
            ],
        ]);
    }

    public function show(MarketplaceListing $listing): View
    {
        $listing->load(['product.materials', 'brand', 'repairOrder.refurbishments', 'repairOrder.consumer', 'offers.buyer']);

        return view('modules.repair.admin.marketplace.show', ['listing' => $listing]);
    }

    public function respond(Request $request, ResaleOffer $offer, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:accept,decline']);
        $service->respondToOffer($offer, $data['decision'] === 'accept');

        return back()->with('success', $data['decision'] === 'accept' ? 'Offre acceptée : annonce réservée.' : 'Offre refusée.');
    }

    public function sold(MarketplaceListing $listing, RepairMarketplaceService $service): RedirectResponse
    {
        $service->markSold($listing);

        return back()->with('success', 'Vente finalisée.');
    }

    public function archive(MarketplaceListing $listing): RedirectResponse
    {
        abort_unless($listing->status === 'published', 409, 'Une annonce réservée ou vendue ne peut pas être archivée.');
        $listing->update(['status' => 'archived']);

        return back()->with('success', 'Annonce archivée.');
    }
}
