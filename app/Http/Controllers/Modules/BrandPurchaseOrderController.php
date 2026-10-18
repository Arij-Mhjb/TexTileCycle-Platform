<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Notifications\SupplierPurchaseOrderMail;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;

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

        return back()->with('success', 'Bon de commande créé. Envoyez-le au fournisseur pour qu’il l’accepte.');
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

    /** Envoie le bon de commande au fournisseur par e-mail ; le bouton de l'e-mail ouvre sa page de réponse. */
    public function send(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->brand_id === $request->user()->brand?->id, 403);
        $supplier = $purchaseOrder->supplier;
        if ($supplier->isSuspended() || ! $purchaseOrder->awaitsSupplierAnswer()) {
            return back()->withErrors(['supplier' => 'Cette commande ne peut plus être envoyée.']);
        }
        if (! $supplier->email) {
            return back()->withErrors(['email' => 'Ajoutez l’e-mail du fournisseur (Modifier) pour lui envoyer la commande.']);
        }

        Notification::route('mail', $supplier->email)->notify(new SupplierPurchaseOrderMail($purchaseOrder));
        $purchaseOrder->update(['sent_at' => now(), 'status' => 'sent']);

        return back()->with('success', 'Commande '.$purchaseOrder->reference.' envoyée par e-mail à '.$supplier->email.'.');
    }

    /**
     * Aperçu de l'e-mail envoyé au fournisseur (environnement local uniquement,
     * où les e-mails sont écrits dans les logs au lieu d'être réellement envoyés).
     */
    public function emailPreview(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        abort_unless(app()->isLocal(), 404);
        abort_unless($purchaseOrder->brand_id === $request->user()->brand?->id, 403);

        return response((new SupplierPurchaseOrderMail($purchaseOrder))->toMail($purchaseOrder->supplier)->render());
    }
}
