<?php

namespace App\Http\Controllers;

use App\Models\ConsumerClaim;
use App\Models\ConsumerNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminConsumerClaimController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.consumer-claims', [
            'claims' => ConsumerClaim::with(['consumer', 'productReturn', 'repairOrder', 'tradeIn'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function update(Request $request, ConsumerClaim $consumerClaim): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'resolved', 'rejected'])],
            'admin_response' => 'required|string|min:5|max:3000',
        ]);
        $consumerClaim->update($data + [
            'resolved_by' => $request->user()->id,
            'resolved_at' => in_array($data['status'], ['resolved', 'rejected'], true) ? now() : null,
        ]);
        ConsumerNotification::create([
            'user_id' => $consumerClaim->user_id, 'type' => 'claim_updated',
            'title' => 'Réclamation '.$consumerClaim->reference.' mise à jour',
            'message' => $data['admin_response'], 'url' => route('consumer.claims.index'),
        ]);

        return back()->with('success', 'Décision transmise au consommateur.');
    }
}
