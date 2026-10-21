<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\ResaleOffer;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandMarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return view('modules.repair.brand.marketplace', [
            'brand' => $brand,
            'listings' => $brand->marketplaceListings()->with(['product', 'offers.buyer', 'repairOrder'])->latest()->get(),
            'products' => $brand->products()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'product_id' => 'required|integer',
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'condition_grade' => 'required|in:A,B,C',
            'price' => 'required|numeric|min:1|max:100000',
            'points_price' => 'nullable|integer|min:1|max:1000000',
        ]);
        $product = $brand->products()->findOrFail($data['product_id']);

        $brand->marketplaceListings()->create([...$data, 'product_id' => $product->id, 'status' => 'published', 'published_at' => now()]);

        return back()->with('success', 'Article reconditionné publié sur le marketplace circulaire.');
    }

    public function respond(Request $request, ResaleOffer $offer, RepairMarketplaceService $service): RedirectResponse
    {
        abort_unless($offer->listing->brand_id === $request->user()->brand?->id, 403);
        $data = $request->validate(['decision' => 'required|in:accept,decline']);
        $service->respondToOffer($offer, $data['decision'] === 'accept');

        return back()->with('success', $data['decision'] === 'accept' ? 'Offre acceptée.' : 'Offre refusée.');
    }

    public function sold(Request $request, MarketplaceListing $listing, RepairMarketplaceService $service): RedirectResponse
    {
        abort_unless($listing->brand_id === $request->user()->brand?->id, 403);
        $service->markSold($listing);

        return back()->with('success', 'Vente finalisée.');
    }
}
