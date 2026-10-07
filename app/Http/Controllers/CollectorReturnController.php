<?php

namespace App\Http\Controllers;

use App\Models\ProductReturn;
use App\Services\MediaStorageService;
use App\Services\ProductReturnWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectorReturnController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProductReturn::with(['product', 'user', 'collectionPoint', 'evidences.user'])
            ->whereIn('status', ['dropoff_expected', 'deposit_declared', 'received', 'inspected', 'routed'])
            ->latest();

        if (! $request->user()->isAdmin()) {
            $query->whereHas('collectionPoint', fn ($point) => $point->where('operator_user_id', $request->user()->id));
        }

        return view('collector.returns', ['items' => $query->paginate(20)]);
    }

    public function update(
        Request $request,
        ProductReturn $productReturn,
        ProductReturnWorkflowService $workflow,
        MediaStorageService $media,
    ): RedirectResponse {
        $this->guard($request, $productReturn);
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

    private function guard(Request $request, ProductReturn $return): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless($return->collectionPoint()->where('operator_user_id', $request->user()->id)->exists(), 403);
    }
}
