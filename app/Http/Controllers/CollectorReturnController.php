<?php

namespace App\Http\Controllers;

use App\Models\MaterialMatch;
use App\Models\ProductReturn;
use App\Models\TradeInRequest;
use App\Services\RepairMarketplaceService;
use App\Services\MaterialExchangeService;
use App\Services\MediaStorageService;
use App\Services\ProductReturnWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CollectorReturnController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProductReturn::with(['product', 'user', 'collectionPoint', 'evidences.user', 'materialMatches.request.brand'])
            ->whereIn('status', ['dropoff_expected', 'deposit_declared', 'received', 'inspected', 'routed', 'marketplace', 'reserved_by_brand', 'in_transit'])
            ->latest();

        if (! $request->user()->isAdmin()) {
            $query->whereHas('collectionPoint', fn ($point) => $point->where('operator_user_id', $request->user()->id));
        }

        $tradeIns = TradeInRequest::with(['consumer', 'product', 'collectionPoint'])
            ->whereIn('status', ['accepted', 'deposited'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->whereHas('collectionPoint', fn ($p) => $p->where('operator_user_id', $request->user()->id)))
            ->latest()->get();

        return view('collector.returns', ['items' => $query->paginate(20), 'tradeIns' => $tradeIns]);
    }

    public function update(
        Request $request,
        ProductReturn $productReturn,
        ProductReturnWorkflowService $workflow,
        MediaStorageService $media,
    ): RedirectResponse {
        $this->guard($request, $productReturn);
        abort_unless(in_array($productReturn->status, ['dropoff_expected', 'deposit_declared', 'received', 'inspected', 'routed'], true), 409, 'Ce textile est déjà dans le circuit de transfert vers une marque.');
        $data = $request->validate([
            'status' => 'required|in:received,inspected,routed,completed,rejected',
            'qr_token' => 'nullable|string|max:100',
            'condition' => 'nullable|in:good,wearable,minor_damage,damaged,unusable,unknown',
            'confirmed_weight_kg' => 'nullable|numeric|min:0|max:999999',
            'evidence' => 'nullable|image|mimes:jpeg,png,webp|max:8192',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($data['status'] === 'received' && ! hash_equals($productReturn->qr_token, (string) ($data['qr_token'] ?? ''))) {
            return back()->withErrors(['qr_token' => 'Le code QR ne correspond pas à ce retour.']);
        }

        if (in_array($data['status'], ['received', 'inspected'], true) && ! $request->hasFile('evidence')) {
            return back()->withErrors(['evidence' => 'Une photo est obligatoire pour cette étape.']);
        }

        $productReturn->update(array_filter([
            'condition' => $data['condition'] ?? null,
            'confirmed_weight_kg' => $data['confirmed_weight_kg'] ?? null,
        ], fn ($value) => $value !== null));

        if ($request->hasFile('evidence')) {
            $stored = $media->storeReturnEvidence($request->file('evidence'), $productReturn->id, $data['status']);
            $productReturn->evidences()->create([
                ...$stored,
                'user_id' => $request->user()->id,
                'type' => 'collector_'.$data['status'],
                'notes' => $data['note'] ?? null,
            ]);
        }

        $workflow->transition($productReturn, $data['status'], $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Étape enregistrée dans la traçabilité.');
    }

    public function transfer(Request $request, MaterialMatch $materialMatch, MaterialExchangeService $exchange, MediaStorageService $media): RedirectResponse
    {
        $return = $materialMatch->productReturn;
        $this->guard($request, $return);
        abort_unless($return->logistics_mode === 'hub_transfer', 403);
        $data = $request->validate(['transfer_token' => 'required|string|max:100', 'evidence' => 'required|image|mimes:jpeg,png,webp|max:8192', 'note' => 'required|string|min:5|max:1000']);
        if (! hash_equals((string) $materialMatch->transfer_token, $data['transfer_token'])) {
            return back()->withErrors(['transfer_token' => 'Le code de transfert ne correspond pas à cette réservation.']);
        }
        $stored = $media->storeReturnEvidence($request->file('evidence'), $return->id, 'hub-transfer');
        try {
            DB::transaction(function () use ($exchange, $materialMatch, $request, $return, $stored, $data) {
                $exchange->transfer($materialMatch, $request->user());
                $return->evidences()->create([...$stored, 'user_id' => $request->user()->id, 'type' => 'collector_transfer', 'notes' => $data['note']]);
            });
        } catch (\Throwable $error) {
            $media->delete($stored['path'], $stored['storage_disk'] ?? null, $stored['cloudinary_public_id'] ?? null, $stored['delivery_type'] ?? null);
            throw $error;
        }

        return back()->with('success', 'Remise à la marque confirmée avec preuve. La marque doit encore confirmer sa réception.');
    }

    public function receiveTradeIn(Request $request, TradeInRequest $tradeIn, MediaStorageService $media, RepairMarketplaceService $service): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort_unless($tradeIn->collectionPoint?->operator_user_id === $request->user()->id, 403);
        }
        abort_unless(in_array($tradeIn->status, ['accepted', 'deposited'], true), 409);
        $data = $request->validate([
            'qr_token' => 'required|string|max:100',
            'evidence' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);
        if (! hash_equals((string) $tradeIn->qr_token, $data['qr_token'])) {
            return back()->withErrors(['qr_token' => 'Le QR ne correspond pas à cette reprise.']);
        }
        $stored = $media->storeReturnEvidence($request->file('evidence'), $tradeIn->id, 'trade-in-received');
        $evidence = $tradeIn->deposit_evidence ?? [];
        $evidence['collector'] = $stored;
        $tradeIn->update(['deposit_evidence' => $evidence, 'received_at' => now()]);
        $service->completeTradeIn($tradeIn->fresh());

        return back()->with('success', 'Reprise reçue : les points du consommateur sont validés.');
    }

    private function guard(Request $request, ProductReturn $return): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless($return->collectionPoint()->where('operator_user_id', $request->user()->id)->exists(), 403);
    }
}
