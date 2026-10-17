<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandSupplierBatchController extends Controller
{
    public function store(Request $request, Supplier $supplier, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
        $data = $request->validate([
            'batch_code' => 'required|string|max:80|unique:supplier_batches,batch_code',
            'material_id' => 'required|exists:materials,id',
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('supplier_id', $supplier->id)],
            'quantity_kg' => 'required|numeric|min:0.1|max:9999999',
            'origin_country' => 'required|string|max:100',
            'production_date' => 'nullable|date|before_or_equal:today',
            'received_at' => 'nullable|date',
        ]);

        $supplier->batches()->create([...$data, 'status' => ! empty($data['received_at']) ? 'received' : 'pending']);
        $traceability->refreshComplianceScore($supplier);

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

    public function certificate(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($batch->supplier->brand_id === $request->user()->brand?->id, 403);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(MaterialOriginCertificate::TYPES))],
            'certificate_number' => 'required|string|max:100',
            'issuer' => 'required|string|max:150',
            'issued_at' => 'required|date|before_or_equal:today',
            'expires_at' => 'nullable|date|after:issued_at',
            'document_url' => 'nullable|url|max:255',
        ]);

        $batch->certificates()->create([...$data, 'status' => 'pending']);
        $traceability->refreshComplianceScore($batch->supplier);

        return back()->with('success', 'Certificat ajouté au lot '.$batch->batch_code.'.');
    }
}
