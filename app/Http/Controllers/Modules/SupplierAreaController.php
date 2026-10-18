<?php

namespace App\Http\Controllers\Modules;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Espace du fournisseur connecté (rôle « supplier ») : ses commandes, accepter / refuser, déclarer l'expédition.
 * Il ne voit que les commandes adressées à SES fiches fournisseur.
 */
class SupplierAreaController extends SupplierPortalController
{
    public function index(Request $request): View
    {
        $orders = PurchaseOrder::with(['brand', 'supplier', 'batches'])
            ->whereHas('supplier', fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereNotIn('status', ['draft'])
            ->orderByRaw("case status when 'sent' then 0 when 'ordered' then 0 when 'accepted' then 1 when 'shipped' then 2 else 3 end")
            ->latest('ordered_at')->get();

        return view('modules.suppliers.area.index', ['orders' => $orders]);
    }

    public function order(Request $request, PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeOrder($request, $purchaseOrder);
        $purchaseOrder->load(['brand', 'supplier', 'batches.material', 'batches.certificates']);

        return view('modules.suppliers.portal', [
            'order' => $purchaseOrder,
            'readOnly' => false,
            'account' => true,
            'materials' => Material::orderBy('name')->get(),
            'shipUrl' => route('supplier.orders.ship', $purchaseOrder),
            'answerUrl' => route('supplier.orders.answer', $purchaseOrder),
        ]);
    }

    public function answerOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);

        return $this->processAnswer($request, $purchaseOrder);
    }

    public function shipOrder(Request $request, PurchaseOrder $purchaseOrder, SupplierTraceabilityService $traceability): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);

        return $this->processShipment($request, $purchaseOrder, $traceability);
    }

    public function editPassword(Request $request): View
    {
        return view('modules.suppliers.area.password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['password' => 'nouveau mot de passe']);

        $request->user()->forceFill(['password' => Hash::make($request->password), 'must_change_password' => false])->save();

        return to_route('supplier.dashboard')->with('success', 'Mot de passe enregistré. Bienvenue dans votre espace fournisseur !');
    }

    private function authorizeOrder(Request $request, PurchaseOrder $purchaseOrder): void
    {
        abort_unless($purchaseOrder->supplier?->user_id === $request->user()->id, 403, 'Cette commande ne vous est pas adressée.');
    }
}
