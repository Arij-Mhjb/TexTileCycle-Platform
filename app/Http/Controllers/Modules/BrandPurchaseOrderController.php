<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Côté marque : passer commande, arbitrer une contre-proposition, réceptionner les livraisons, solder ou annuler. */
class BrandPurchaseOrderController extends Controller
{
    public function store(Request $request, Supplier $supplier, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
        if ($supplier->isSuspended()) {
            return back()->withErrors(['supplier' => 'Ce fournisseur est suspendu : réactivez-le pour lui passer une commande.']);
        }
        $data = $request->validate([
            'ordered_at' => 'required|date',
            'expected_at' => 'nullable|date|after_or_equal:ordered_at',
            'notes' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1|max:10',
            'lines.*.material_id' => 'required|distinct|exists:materials,id',
            'lines.*.quantity_kg' => 'required|numeric|min:0.1|max:9999999',
            'lines.*.unit_price' => 'nullable|numeric|min:0|max:999999',
        ], [
            'lines.required' => 'Ajoutez au moins une ligne (matière et quantité).',
            'lines.*.material_id.distinct' => 'Chaque matière ne peut apparaître qu’une fois : regroupez les quantités sur une seule ligne.',
        ], [
            'lines.*.material_id' => 'matière', 'lines.*.quantity_kg' => 'quantité', 'lines.*.unit_price' => 'prix unitaire',
            'expected_at' => 'livraison souhaitée', 'ordered_at' => 'date de commande',
        ]);

        $order = $orders->create($supplier, $data, array_values($data['lines']), $request->user());

        return to_route('brand.purchase-orders.show', $order)
            ->with('success', 'Bon de commande '.$order->reference.' envoyé : '.$supplier->name.' le retrouve dans son espace pour l’accepter, le refuser ou faire une contre-proposition.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeOrder($request, $purchaseOrder);
        $purchaseOrder->load(['supplier.user', 'lines.material', 'lines.batches.certificates', 'events.user', 'batches']);

        return view('modules.suppliers.brand.order', ['order' => $purchaseOrder]);
    }

    public function decideCounter(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $orders): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);
        $data = $request->validate([
            'decision' => 'required|in:accept,reject',
            'reason' => 'nullable|required_if:decision,reject|string|max:500',
        ], ['reason.required_if' => 'Expliquez au fournisseur pourquoi vous refusez sa proposition.'], ['reason' => 'raison']);

        $orders->decideCounter($purchaseOrder, $data['decision'] === 'accept', $data['reason'] ?? null, $request->user());

        return back()->with('success', $data['decision'] === 'accept' ? 'Contre-proposition acceptée : le fournisseur peut expédier.' : 'Contre-proposition refusée : la commande est annulée.');
    }

    public function receive(Request $request, SupplierBatch $batch, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($batch->purchaseOrder && $batch->supplier?->brand_id === $request->user()->brand?->id, 403);
        $orders->receive($batch, $request->user());

        return back()->with('success', 'Livraison '.$batch->batch_code.' réceptionnée.');
    }

    public function closeShort(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $orders): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);
        $data = $request->validate(['close_reason' => 'required|string|max:500'], [], ['close_reason' => 'raison']);
        $orders->closeShort($purchaseOrder, $data['close_reason'], $request->user());

        return back()->with('success', 'Commande soldée : le reliquat ne sera pas livré.');
    }

    /** Annulation par la marque (tant que rien n'est expédié). */
    public function update(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $orders): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);
        $data = $request->validate(['status' => 'required|in:cancelled', 'close_reason' => 'nullable|string|max:500']);
        $orders->cancel($purchaseOrder, $data['close_reason'] ?? null, $request->user());

        return back()->with('success', 'Commande '.$purchaseOrder->reference.' annulée.');
    }

    private function authorizeOrder(Request $request, PurchaseOrder $purchaseOrder): void
    {
        abort_unless($purchaseOrder->brand_id === $request->user()->brand?->id, 403);
    }
}
