<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MaterialOriginCertificate;
use App\Models\PurchaseOrder;
use App\Models\SupplierBatch;
use App\Services\PurchaseOrderService;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Espace du fournisseur connecté (rôle « supplier ») : ses commandes, accepter / refuser / contre-proposer,
 * puis expédier en une ou plusieurs livraisons.
 * Il ne voit que les commandes adressées à SES fiches fournisseur.
 */
class SupplierAreaController extends Controller
{
    public function index(Request $request): View
    {
        $orders = PurchaseOrder::with(['brand', 'supplier', 'lines.batches', 'lines.material'])
            ->whereHas('supplier', fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereNotIn('status', ['draft'])
            ->orderByRaw("case status when 'sent' then 0 when 'ordered' then 0 when 'accepted' then 1 when 'partially_shipped' then 1 when 'countered' then 2 when 'shipped' then 3 else 4 end")
            ->latest('ordered_at')->get();

        return view('modules.suppliers.area.index', ['orders' => $orders]);
    }

    public function order(Request $request, PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeOrder($request, $purchaseOrder);
        $purchaseOrder->load(['brand', 'supplier', 'lines.material', 'lines.batches.certificates', 'events']);

        return view('modules.suppliers.area.order', [
            'order' => $purchaseOrder,
            'shipUrl' => route('supplier.orders.ship', $purchaseOrder),
            'answerUrl' => route('supplier.orders.answer', $purchaseOrder),
        ]);
    }

    public function answerOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);

        return $this->processAnswer($request, $purchaseOrder, app(PurchaseOrderService::class));
    }

    public function shipOrder(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $orders): RedirectResponse
    {
        $this->authorizeOrder($request, $purchaseOrder);

        return $this->processShipment($request, $purchaseOrder, $orders);
    }

    /** Tous les lots livrés par ce fournisseur, avec leurs certificats. */
    public function batches(Request $request): View
    {
        $batches = SupplierBatch::with(['material', 'certificates', 'purchaseOrder.brand', 'supplier'])
            ->whereHas('supplier', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()->get();

        return view('modules.suppliers.area.batches', ['batches' => $batches]);
    }

    /** Le fournisseur ajoute (ou remplace) le certificat d'une de ses livraisons : n'importe quelle image, l'IA dit ce qu'elle voit. */
    public function addCertificate(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($batch->supplier?->user_id === $request->user()->id, 403, 'Ce lot n’est pas le vôtre.');
        $request->validate(
            ['document' => 'required|file|mimes:jpg,jpeg,png,webp,gif,bmp,heic,heif|max:10240'],
            ['document.required' => 'Choisissez la photo du certificat.'],
            ['document' => 'photo du certificat']
        );

        $outcome = $traceability->addCertificateFromPhoto($batch, $request->file('document'));
        if ($outcome['recognized'] && $batch->purchaseOrder) {
            app(PurchaseOrderService::class)->log($batch->purchaseOrder, 'supplier', 'certificate_added', 'Certificat ajouté sur le lot '.$batch->batch_code.'.', $request->user());
        }

        return back()->with($outcome['recognized'] ? 'success' : 'warning', $outcome['message']);
    }

    public function certificateDocument(Request $request, MaterialOriginCertificate $certificate): StreamedResponse
    {
        abort_unless($certificate->batch?->supplier?->user_id === $request->user()->id, 403);
        abort_unless($certificate->document_path && Storage::disk('local')->exists($certificate->document_path), 404);

        return Storage::disk('local')->response($certificate->document_path);
    }

    /** Accepter, refuser ou contre-proposer (quantités par ligne, nouvelle date). */
    private function processAnswer(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($purchaseOrder->awaitsSupplierAnswer() && ! $purchaseOrder->supplier->isSuspended(), 410, 'Cette commande a déjà reçu une réponse.');
        $data = $request->validate([
            'decision' => 'required|in:accept,counter,decline',
            'decline_reason' => 'nullable|required_if:decision,decline|string|max:1000',
            'confirmed' => 'nullable|array',
            'confirmed.*' => 'nullable|numeric|min:0|max:9999999',
            'proposed_expected_at' => 'nullable|date|after_or_equal:today',
            'supplier_note' => 'nullable|string|max:1000',
        ], ['decline_reason.required_if' => 'Indiquez la raison du refus.'], [
            'decline_reason' => 'raison du refus', 'confirmed.*' => 'quantité confirmée', 'proposed_expected_at' => 'date proposée', 'supplier_note' => 'message',
        ]);

        $orders->supplierAnswer($purchaseOrder, $data['decision'], $data, $request->user());

        return back()->with('success', match ($data['decision']) {
            'accept' => 'Merci, commande acceptée ! Expédiez-la en une ou plusieurs livraisons ci-dessous.',
            'counter' => 'Votre contre-proposition a été envoyée à '.$purchaseOrder->brand->name.'.',
            default => 'Votre refus a été transmis à '.$purchaseOrder->brand->name.'.',
        });
    }

    /** Expédier une livraison (partielle ou complète) sur une ligne de la commande. */
    private function processShipment(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($purchaseOrder->acceptsShipment(), 410, 'Acceptez d’abord la commande.');
        $data = $request->validate([
            'purchase_order_line_id' => 'required|integer',
            'quantity_kg' => 'required|numeric|min:0.1|max:9999999',
            'origin_country' => 'required|string|max:100',
            'production_date' => 'nullable|date|before_or_equal:today',
            'certificate' => 'required|file|mimes:jpg,jpeg,png,webp,gif,bmp,heic,heif|max:10240',
            'supplier_note' => 'nullable|string|max:1000',
        ], ['certificate.required' => 'Joignez la photo du certificat de provenance.'], [
            'purchase_order_line_id' => 'ligne de commande', 'quantity_kg' => 'quantité', 'origin_country' => 'pays d’origine',
            'production_date' => 'date de production', 'certificate' => 'certificat', 'supplier_note' => 'message',
        ]);
        $line = $purchaseOrder->lines()->findOrFail($data['purchase_order_line_id']);

        $result = $orders->ship($purchaseOrder, $line, $data, $request->file('certificate'), $request->user());

        return back()->with($result['recognized'] ? 'success' : 'warning', 'Livraison enregistrée (lot '.$result['batch']->batch_code.'). '.$result['message']);
    }

    private function authorizeOrder(Request $request, PurchaseOrder $purchaseOrder): void
    {
        abort_unless($purchaseOrder->supplier?->user_id === $request->user()->id, 403, 'Cette commande ne vous est pas adressée.');
    }
}
