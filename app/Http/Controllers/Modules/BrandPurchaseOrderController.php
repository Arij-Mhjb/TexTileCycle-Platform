<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandPurchaseOrderController extends Controller
{
    public function store(Request $request, Supplier $supplier, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
        if ($supplier->isSuspended()) {
            return back()->withErrors(['supplier' => 'Ce fournisseur est suspendu : réactivez-le pour lui passer une commande.']);
        }
        $data = $request->validate([
            'ordered_at' => 'required|date',
            'expected_at' => 'nullable|date|after_or_equal:ordered_at',
            'total_amount' => 'nullable|numeric|min:0|max:9999999',
            'notes' => 'nullable|string|max:1000',
        ]);

        $supplier->purchaseOrders()->create([
            ...$data,
            'brand_id' => $supplier->brand_id,
            'reference' => $traceability->nextPurchaseOrderReference(),
            'status' => 'ordered',
        ]);

        return back()->with('success', 'Bon de commande créé : le fournisseur le retrouve dans son espace pour l’accepter ou le refuser.');
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->brand_id === $request->user()->brand?->id, 403);
        abort_unless($purchaseOrder->isOpen(), 422, 'Cette commande est clôturée.');
        $data = $request->validate(['status' => 'required|in:received,cancelled']);
        $purchaseOrder->update([
            'status' => $data['status'],
            'received_at' => $data['status'] === 'received' ? now() : null,
        ]);

        return back()->with('success', 'Commande '.$purchaseOrder->reference.' : '.$purchaseOrder->statusLabel().'.');
    }
}
