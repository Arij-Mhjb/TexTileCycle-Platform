<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::with('brand')
            ->withCount(['batches', 'batches as pending_batches_count' => fn ($query) => $query->whereIn('status', ['pending', 'received'])])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('country', 'like', '%'.$request->search.'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('modules.suppliers.admin.index', [
            'suppliers' => $suppliers,
            'pendingBatches' => SupplierBatch::with(['supplier.brand', 'material', 'certificates'])->whereIn('status', ['pending', 'received'])->latest()->take(8)->get(),
            'pendingCertificates' => MaterialOriginCertificate::with('batch.supplier.brand')->where('status', 'pending')->latest()->take(8)->get(),
            'stats' => [
                'suppliers' => Supplier::count(),
                'batches' => SupplierBatch::count(),
                'pending' => SupplierBatch::whereIn('status', ['pending', 'received'])->count() + MaterialOriginCertificate::where('status', 'pending')->count(),
                'score' => (int) round(Supplier::avg('compliance_score') ?? 0),
            ],
        ]);
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['brand', 'purchaseOrders', 'batches.material', 'batches.certificates.reviewer', 'batches.verifier', 'batches.productLinks.product']);

        return view('modules.suppliers.admin.show', ['supplier' => $supplier]);
    }

    public function reviewBatch(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:verified,rejected', 'note' => 'nullable|string|max:1000']);
        $traceability->reviewBatch($batch, $request->user(), $data['status'], $data['note'] ?? null);

        return back()->with('success', 'Lot '.$batch->batch_code.' : '.$batch->statusLabel().'.');
    }

    public function reviewCertificate(Request $request, MaterialOriginCertificate $certificate, SupplierTraceabilityService $traceability): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:verified,rejected', 'note' => 'nullable|string|max:1000']);
        $traceability->reviewCertificate($certificate, $request->user(), $data['status'], $data['note'] ?? null);

        return back()->with('success', $certificate->displayName().' : '.$certificate->statusLabel().'.');
    }

    public function document(MaterialOriginCertificate $certificate): StreamedResponse
    {
        abort_unless($certificate->document_path && Storage::disk('local')->exists($certificate->document_path), 404);

        return Storage::disk('local')->response($certificate->document_path);
    }
}
