<?php

namespace App\Http\Controllers;

use App\Models\ConsumerClaim;
use App\Models\ConsumerNotification;
use App\Services\NotificationCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsumerClaimController extends Controller
{
    public function index(Request $request): View
    {
        return view('consumer.claims', [
            'claims' => $request->user()->consumerClaims()->with(['productReturn.product', 'repairOrder.product', 'tradeIn'])->latest()->paginate(12),
            'returns' => $request->user()->productReturns()->with('product')->latest()->get(),
            'repairs' => $request->user()->repairOrders()->with('product')->latest()->get(),
            'tradeIns' => $request->user()->tradeInRequests()->latest()->get(),
        ]);
    }

    public function store(Request $request, NotificationCenter $notifications): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(ConsumerClaim::CATEGORIES))],
            'subject' => 'required|string|min:5|max:150', 'description' => 'required|string|min:20|max:3000',
            'product_return_id' => [
                'nullable', Rule::exists('product_returns', 'id')->where('user_id', $request->user()->id),
            ],
            'repair_order_id' => [
                'nullable', Rule::exists('repair_orders', 'id')->where('user_id', $request->user()->id),
            ],
            'trade_in_request_id' => [
                'nullable', Rule::exists('trade_in_requests', 'id')->where('user_id', $request->user()->id),
            ],
        ]);
        $claim = $request->user()->consumerClaims()->create($data + [
            'reference' => 'CLM-'.now()->format('ymd').'-'.Str::upper(Str::random(5)), 'status' => 'open',
        ]);
        $notifications->toAdmins('consumer_claim', 'Nouvelle réclamation '.$claim->reference,
            $request->user()->name.' · '.$claim->subject, route('admin.consumer-claims.index'));

        return back()->with('success', 'Réclamation enregistrée sous la référence '.$claim->reference.'.');
    }
}
