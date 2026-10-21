<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceOrder;
use App\Models\CollectionPoint;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsumerMarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        return view('modules.repair.consumer.marketplace', [
            'listings' => MarketplaceListing::with(['product', 'brand'])->where('status', 'published')
                ->when($request->filled('grade'), fn ($query) => $query->where('condition_grade', $request->grade))
                ->when($request->boolean('points_only'), fn ($query) => $query->whereNotNull('points_price'))
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->search.'%'))
                ->latest('published_at')->paginate(12)->withQueryString(),
            'myOffers' => $request->user()->resaleOffers()->with('listing')->latest()->take(5)->get(),
            'myOrders' => $request->user()->marketplaceOrders()->with('listing')->latest()->take(5)->get(),
            'pointsBalance' => $this->pointsBalance($request),
        ]);
    }

    public function show(Request $request, MarketplaceListing $listing): View
    {
        abort_unless(in_array($listing->status, ['published', 'reserved', 'sold'], true) || $listing->repairOrder?->user_id === $request->user()->id, 404);
        $listing->load(['product.materials', 'brand', 'repairOrder.refurbishments', 'order.collectionPoint']);

        return view('modules.repair.consumer.listing', [
            'listing' => $listing,
            'myOffer' => $listing->offers()->where('user_id', $request->user()->id)->latest()->first(),
            'myOrder' => $listing->order?->buyer_id === $request->user()->id ? $listing->order : null,
            'pickupPoints' => CollectionPoint::where('is_active', true)->orderBy('city')->get(),
            'pointsBalance' => $this->pointsBalance($request),
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

    public function checkout(Request $request, MarketplaceOrder $marketplaceOrder): RedirectResponse
    {
        abort_unless($marketplaceOrder->buyer_id === $request->user()->id && $marketplaceOrder->status === 'awaiting_checkout', 403);
        $data = $request->validate([
            'fulfilment_method' => 'required|in:pickup,delivery',
            'collection_point_id' => ['nullable', 'required_if:fulfilment_method,pickup', Rule::exists('collection_points', 'id')->where('is_active', true)],
            'delivery_address' => 'nullable|required_if:fulfilment_method,delivery|string|min:10|max:500',
            'payment_method' => 'required|in:pay_on_pickup,cash_on_delivery',
        ]);
        $expectedPayment = $data['fulfilment_method'] === 'pickup' ? 'pay_on_pickup' : 'cash_on_delivery';
        if ($data['payment_method'] !== $expectedPayment) {
            throw ValidationException::withMessages(['payment_method' => 'Le mode de paiement ne correspond pas au mode de remise.']);
        }
        if ($data['fulfilment_method'] === 'pickup') {
            $data['delivery_address'] = null;
        } else {
            $data['collection_point_id'] = null;
        }
        $marketplaceOrder->update($data + ['status' => 'confirmed', 'checked_out_at' => now()]);

        return back()->with('success', 'Commande confirmée. Vous serez informé lorsqu’elle sera prête ou expédiée.');
    }

    public function buyWithPoints(Request $request, MarketplaceListing $listing, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'fulfilment_method' => 'required|in:pickup,delivery',
            'collection_point_id' => ['nullable', 'required_if:fulfilment_method,pickup', Rule::exists('collection_points', 'id')->where('is_active', true)],
            'delivery_address' => 'nullable|required_if:fulfilment_method,delivery|string|min:10|max:500',
        ]);
        if ($data['fulfilment_method'] === 'pickup') {
            $data['delivery_address'] = null;
        } else {
            $data['collection_point_id'] = null;
        }

        $order = $service->purchaseWithPoints($listing, $request->user(), $data);

        return to_route('consumer.marketplace.show', $listing)
            ->with('success', 'Commande '.$order->reference.' confirmée : '.$order->points_spent.' points utilisés.');
    }

    public function confirmReceipt(Request $request, MarketplaceOrder $marketplaceOrder): RedirectResponse
    {
        abort_unless($marketplaceOrder->buyer_id === $request->user()->id, 403);
        abort_unless(in_array($marketplaceOrder->status, ['ready', 'shipped'], true), 409);
        $marketplaceOrder->update(['status' => 'completed', 'received_at' => now()]);
        $marketplaceOrder->listing->update(['status' => 'sold', 'sold_at' => now()]);

        return back()->with('success', 'Réception confirmée. Merci de prolonger la vie de ce vêtement.');
    }

    private function pointsBalance(Request $request): int
    {
        return (int) $request->user()->rewardTransactions()->whereIn('status', ['validated', 'spent'])->sum('points');
    }
}
