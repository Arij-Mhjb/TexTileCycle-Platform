<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MaterialOriginCertificate;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Services\PurchaseOrderService;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandSupplierBatchController extends Controller
{
    public function store(Request $request, Supplier $supplier, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('supplier_id', $supplier->id)],
            'quantity_kg' => 'required|numeric|min:0.1|max:9999999',
            'origin_country' => 'required|string|max:100',
            'production_date' => 'nullable|date|before_or_equal:today',
            'received_at' => 'nullable|date',
        ]);

        // Livraison saisie par la marque sur une commande : elle compte sur la ligne de la même matière.
        $order = ! empty($data['purchase_order_id']) ? PurchaseOrder::with('lines')->find($data['purchase_order_id']) : null;
        $line = $order?->lines->firstWhere('material_id', (int) $data['material_id']);
        if ($order && $order->lines->isNotEmpty() && ! $line) {
            return back()->withInput()->withErrors(['material_id' => 'Cette matière ne figure pas sur la commande '.$order->reference.'.']);
        }

        $data['batch_code'] = $traceability->nextBatchCode($supplier);
        $batch = $supplier->batches()->create([...$data, 'purchase_order_line_id' => $line?->id, 'status' => ! empty($data['received_at']) ? 'received' : 'pending']);
        $traceability->refreshComplianceScore($supplier);
        if ($order) {
            $orders = app(PurchaseOrderService::class);
            $orders->log($order, 'brand', 'delivery_recorded', 'Livraison saisie par la marque : lot '.$batch->batch_code.' ('.$batch->quantity_kg.' kg).', $request->user());
            $orders->refreshStatus($order);
        }

        return back()->with('success', 'Lot '.$data['batch_code'].' enregistré. Il sera vérifié par TexTileCycle.');
    }

    public function destroy(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($batch->supplier->brand_id === $request->user()->brand?->id, 403);
        abort_if($batch->status === 'verified', 422, 'Un lot vérifié ne peut plus être supprimé.');
        $supplier = $batch->supplier;
        $batch->delete();
        $traceability->refreshComplianceScore($supplier);

        return back()->with('success', 'Lot supprimé.');
    }

    /**
     * La marque envoie seulement la photo du certificat : l'IA lit le type, le numéro,
     * l'organisme et les dates, et le certificat est enregistré (en attente de vérification admin).
     */
    /**
     * La marque envoie seulement la photo du certificat : l'IA lit le type, le numéro,
     * l'organisme et les dates (voir SupplierTraceabilityService::addCertificateFromPhoto).
     */
    public function certificate(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($batch->supplier->brand_id === $request->user()->brand?->id, 403);
        $request->validate(
            ['document' => 'required|file|mimes:jpg,jpeg,png,webp,gif,bmp,heic,heif|max:10240'],
            ['document.required' => 'Ajoutez la photo ou le scan du certificat.'],
            ['document' => 'document du certificat']
        );

        $outcome = $traceability->addCertificateFromPhoto($batch, $request->file('document'));

        return $outcome['recognized'] ? back()->with('success', $outcome['message']) : back()->with('warning', $outcome['message']);
    }

    public function document(Request $request, MaterialOriginCertificate $certificate): StreamedResponse
    {
        abort_unless($certificate->batch->supplier->brand_id === $request->user()->brand?->id, 403);
        abort_unless($certificate->document_path && Storage::disk('local')->exists($certificate->document_path), 404);

        return Storage::disk('local')->response($certificate->document_path);
    }
}
