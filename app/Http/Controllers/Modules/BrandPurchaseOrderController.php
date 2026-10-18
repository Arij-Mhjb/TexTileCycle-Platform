<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Notifications\SupplierPurchaseOrderMail;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class BrandPurchaseOrderController extends Controller
{
    public function store(Request $request, Supplier $supplier, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
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

        return back()->with('success', 'Bon de commande créé.');
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->brand_id === $request->user()->brand?->id, 403);
        $data = $request->validate(['status' => 'required|in:received,cancelled']);
        $purchaseOrder->update([
            'status' => $data['status'],
            'received_at' => $data['status'] === 'received' ? now() : null,
        ]);

        return back()->with('success', 'Commande '.$purchaseOrder->reference.' : '.$purchaseOrder->statusLabel().'.');
    }

    /** Envoie le bon de commande au fournisseur par e-mail, avec son lien sécurisé. */
    public function send(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->brand_id === $request->user()->brand?->id, 403);
        $supplier = $purchaseOrder->supplier;
        if (! $supplier->email) {
            return back()->withErrors(['email' => 'Ajoutez l’e-mail du fournisseur (Modifier) pour lui envoyer la commande.']);
        }

        Notification::route('mail', $supplier->email)->notify(new SupplierPurchaseOrderMail($purchaseOrder));
        $purchaseOrder->update(['sent_at' => now(), 'status' => $purchaseOrder->status === 'ordered' ? 'sent' : $purchaseOrder->status]);

        return back()->with('success', 'Commande '.$purchaseOrder->reference.' envoyée à '.$supplier->email.'.');
    }
}
