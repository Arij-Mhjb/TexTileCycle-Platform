<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\BrandNotification;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Espace du fournisseur connecté (rôle « supplier ») : ses commandes, accepter / refuser, déclarer l'expédition.
 * Il ne voit que les commandes adressées à SES fiches fournisseur.
 */
class SupplierAreaController extends Controller
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

        return view('modules.suppliers.area.order', [
            'order' => $purchaseOrder,
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

    /** accepter / refuser. */
    private function processAnswer(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->awaitsSupplierAnswer() && ! $purchaseOrder->supplier->isSuspended(), 410, 'Cette commande a déjà reçu une réponse.');
        $data = $request->validate([
            'decision' => 'required|in:accept,decline',
            'decline_reason' => 'nullable|required_if:decision,decline|string|max:1000',
        ], ['decline_reason.required_if' => 'Indiquez la raison du refus.'], ['decline_reason' => 'raison du refus']);

        $accepted = $data['decision'] === 'accept';
        $purchaseOrder->update($accepted
            ? ['status' => 'accepted', 'accepted_at' => now()]
            : ['status' => 'declined', 'declined_at' => now(), 'decline_reason' => $data['decline_reason']]);

        BrandNotification::create([
            'user_id' => $purchaseOrder->brand->user_id,
            'type' => $accepted ? 'supplier_order_accepted' : 'supplier_order_declined',
            'title' => $purchaseOrder->supplier->name.($accepted ? ' a accepté la commande' : ' a refusé la commande'),
            'message' => 'Commande '.$purchaseOrder->reference.($accepted ? ' acceptée.' : ' refusée : '.$data['decline_reason']),
            'url' => route('brand.suppliers.show', $purchaseOrder->supplier),
        ]);

        return back()->with('success', $accepted
            ? 'Merci, commande acceptée ! Déclarez l’expédition ci-dessous lorsque la matière part.'
            : 'Votre refus a été transmis à '.$purchaseOrder->brand->name.'.');
    }

    /** déclarer une expédition. */
    private function processShipment(Request $request, PurchaseOrder $purchaseOrder, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($purchaseOrder->acceptsShipment(), 410, 'Acceptez d’abord la commande.');
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity_kg' => 'required|numeric|min:0.1|max:9999999',
            'origin_country' => 'required|string|max:100',
            'production_date' => 'nullable|date|before_or_equal:today',
            'certificate' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
            'supplier_note' => 'nullable|string|max:1000',
        ], ['certificate.required' => 'Joignez la photo du certificat de provenance.'], [
            'material_id' => 'matière', 'quantity_kg' => 'quantité', 'origin_country' => 'pays d’origine',
            'production_date' => 'date de production', 'certificate' => 'certificat', 'supplier_note' => 'message',
        ]);

        $supplier = $purchaseOrder->supplier;
        DB::beginTransaction();
        try {
            $batch = $supplier->batches()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'batch_code' => $traceability->nextBatchCode($supplier),
                'material_id' => $data['material_id'],
                'quantity_kg' => $data['quantity_kg'],
                'origin_country' => $data['origin_country'],
                'production_date' => $data['production_date'] ?? null,
                'status' => 'pending',
            ]);
            $outcome = $traceability->addCertificateFromPhoto($batch, $request->file('certificate'));
            if (! $outcome['ok']) {
                // Pas un certificat : on annule la création du lot.
                DB::rollBack();

                return back()->withInput()->withErrors(['certificate' => $outcome['message']]);
            }
            $purchaseOrder->update([
                'status' => 'shipped',
                'shipped_at' => now(),
                'supplier_note' => $data['supplier_note'] ?? $purchaseOrder->supplier_note,
            ]);
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        BrandNotification::create([
            'user_id' => $purchaseOrder->brand->user_id,
            'type' => 'supplier_shipment',
            'title' => 'Expédition déclarée par '.$supplier->name,
            'message' => 'Commande '.$purchaseOrder->reference.' : lot '.$batch->batch_code.' ('.$data['quantity_kg'].' kg) avec certificat lu par l’IA. Confirmez la réception à l’arrivée.',
            'url' => route('brand.suppliers.show', $supplier),
        ]);

        return back()->with('success', 'Merci ! Expédition enregistrée (lot '.$batch->batch_code.'). '.$outcome['message']);
    }

    private function authorizeOrder(Request $request, PurchaseOrder $purchaseOrder): void
    {
        abort_unless($purchaseOrder->supplier?->user_id === $request->user()->id, 403, 'Cette commande ne vous est pas adressée.');
    }
}
