<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Scan;
use App\Services\ProductReturnWorkflowService;
use App\Services\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReturnController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProductReturn::with(['product', 'collectionPoint.recoveryProgram', 'rewardTransaction']);

        if ($request->user()->isConsumer()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('product-returns.index', ['items' => $query->latest()->paginate(10)->withQueryString()]);
    }

    public function create(Request $request): View
    {
        $item = new ProductReturn([
            'product_id' => $request->integer('product') ?: null,
            'collection_point_id' => $request->integer('point') ?: null,
            'scan_id' => $request->integer('scan') ?: null,
            'action' => $request->string('action')->toString() ?: 'recycling',
            'condition' => $request->string('condition')->toString() ?: null,
            'returned_at' => now()->addDay(),
        ]);

        return view('product-returns.create', $this->formData($item));
    }

    public function store(Request $request, ProductReturnWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);
        $this->guardScan($request, $data['scan_id'] ?? null);

        $return = $workflow->create($request->user(), $data);
        $return = $workflow->transition($return, 'dropoff_expected', $request->user(), 'Dépôt préparé et QR généré.');

        return to_route('product-returns.show', $return)
            ->with('success', 'Retour enregistré. Présentez le QR au point sélectionné.');
    }

    public function show(Request $request, ProductReturn $productReturn): View
    {
        $this->guard($request, $productReturn);

        return view('product-returns.show', [
            'item' => $productReturn->load([
                'product.materials', 'collectionPoint.recoveryProgram', 'statusHistory.user',
                'rewardTransaction', 'treatmentResult', 'scan.assets', 'materialMatches.request.brand', 'evidences.user',
            ]),
        ]);
    }

    public function declareDeposit(
        Request $request,
        ProductReturn $productReturn,
        ProductReturnWorkflowService $workflow,
        MediaStorageService $media,
    ): RedirectResponse {
        $this->guard($request, $productReturn);
        abort_unless($request->user()->isConsumer() && $productReturn->status === 'dropoff_expected', 403);

        $data = $request->validate([
            'evidence' => 'required|image|mimes:jpeg,png,webp|max:8192',
            'note' => 'nullable|string|max:500',
        ]);
        $stored = $media->storeReturnEvidence($request->file('evidence'), $productReturn->id, 'consumer-deposit');
        $productReturn->evidences()->create([
            ...$stored,
            'user_id' => $request->user()->id,
            'type' => 'consumer_deposit',
            'notes' => $data['note'] ?? null,
        ]);
        $workflow->transition($productReturn, 'deposit_declared', $request->user(), 'Dépôt déclaré avec preuve photo.');

        return to_route('product-returns.show', $productReturn)->with('success', 'Preuve de dépôt transmise au collecteur.');
    }

    public function edit(Request $request, ProductReturn $productReturn): View
    {
        $this->guard($request, $productReturn);

        if ($request->user()->isConsumer()) {
            abort_unless($productReturn->canConsumerEdit(), 403);
        }

        return view('product-returns.edit', $this->formData($productReturn));
    }

    public function update(Request $request, ProductReturn $productReturn, ProductReturnWorkflowService $workflow): RedirectResponse
    {
        $this->guard($request, $productReturn);

        if ($request->user()->isConsumer()) {
            abort_unless($productReturn->canConsumerEdit(), 403);
            $data = $this->validated($request);
            $this->guardScan($request, $data['scan_id'] ?? null);
            $workflow->revise($productReturn, $data, $request->user());

            return to_route('product-returns.show', $productReturn)->with('success', 'Retour mis à jour.');
        }

        $data = $this->validated($request, true);
        $status = $data['status'];
        $note = $data['status_note'] ?? null;
        unset($data['status'], $data['status_note']);

        $productReturn = $workflow->revise($productReturn, $data, $request->user());
        $workflow->transition($productReturn, $status, $request->user(), $note);

        return to_route('product-returns.show', $productReturn)->with('success', 'Retour et statut mis à jour.');
    }

    public function destroy(Request $request, ProductReturn $productReturn, ProductReturnWorkflowService $workflow): RedirectResponse
    {
        $this->guard($request, $productReturn);

        if ($request->user()->isConsumer()) {
            abort_unless($productReturn->canConsumerEdit(), 403);
            $workflow->transition($productReturn, 'cancelled', $request->user(), 'Retour annulé par le consommateur.');

            return to_route('product-returns.index')->with('success', 'Retour annulé.');
        }

        $productReturn->delete();

        return to_route('product-returns.index')->with('success', 'Retour supprimé.');
    }

    private function validated(Request $request, bool $admin = false): array
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'collection_point_id' => 'required|exists:collection_points,id',
            'scan_id' => 'nullable|exists:scans,id',
            'action' => 'required|in:repair,donation,take_back,recycling,reuse',
            'condition' => 'nullable|in:good,wearable,minor_damage,damaged,unusable,unknown',
            'weight_kg' => 'nullable|numeric|min:0|max:999999',
            'returned_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($admin) {
            $rules += [
                'status' => 'required|in:declared,dropoff_expected,deposit_declared,received,inspected,routed,completed,rejected,cancelled',
                'confirmed_weight_kg' => 'nullable|numeric|min:0|max:999999',
                'status_note' => 'nullable|string|max:1000',
            ];
        }

        return $request->validate($rules);
    }

    private function formData(ProductReturn $item): array
    {
        return [
            'item' => $item,
            'products' => Product::where('status', 'published')->where('is_active', true)->orderBy('name')->get(),
            'points' => CollectionPoint::where('is_active', true)
                ->whereHas('recoveryProgram', fn ($program) => $program->where('is_active', true))
                ->with('recoveryProgram')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function guard(Request $request, ProductReturn $return): void
    {
        abort_if($request->user()->isConsumer() && $return->user_id !== $request->user()->id, 403);
    }

    private function guardScan(Request $request, ?int $scanId): void
    {
        if ($request->user()->isConsumer() && $scanId) {
            $isAllowed = Scan::whereKey($scanId)
                ->where('user_id', $request->user()->id)
                ->where(fn ($query) => $query
                    ->where('source_type', '!=', 'multimodal')
                    ->orWhereNotNull('consumer_confirmed_at'))
                ->exists();
            abort_unless($isAllowed, 403);
        }
    }
}

