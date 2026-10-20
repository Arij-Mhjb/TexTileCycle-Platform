<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ProductSupplierLink;
use App\Models\SupplierBatch;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandTraceabilityController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();
        $products = $brand->products()->with(['materials', 'supplierLinks.batch.supplier', 'supplierLinks.batch.material'])->orderBy('name')->get();
        $batches = SupplierBatch::with(['supplier', 'material'])->whereIn('supplier_id', $brand->suppliers()->pluck('id'))->latest()->get();

        return view('modules.suppliers.brand.traceability', [
            'products' => $products,
            'batches' => $batches,
            'coverage' => $products->isEmpty() ? 0 : (int) round($products->filter(fn ($product) => $product->supplierLinks->isNotEmpty())->count() / $products->count() * 100),
        ]);
    }

    public function store(Request $request, SupplierTraceabilityService $traceability): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_batch_id' => 'required|exists:supplier_batches,id',
            'component' => 'nullable|string|max:100',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        $product = $brand->products()->findOrFail($data['product_id']);
        $batch = SupplierBatch::with('supplier')->findOrFail($data['supplier_batch_id']);
        abort_unless($batch->supplier->brand_id === $brand->id, 403);

        $link = ProductSupplierLink::updateOrCreate(
            ['product_id' => $product->id, 'supplier_batch_id' => $batch->id],
            ['component' => $data['component'] ?? null, 'percentage' => $data['percentage'] ?? null]
        );
        $issues = $traceability->checkConformity($link);

        return back()->with('success', $issues === []
            ? 'Lot associé à « '.$product->name.' » : conforme à la déclaration.'
            : 'Lot associé à « '.$product->name.' » : '.count($issues).' écart(s) à corriger.');
    }

    public function check(Request $request, ProductSupplierLink $link, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($link->product->brand_id === $request->user()->brand?->id, 403);
        $issues = $traceability->checkConformity($link);

        return back()->with('success', $issues === [] ? 'Contrôle réussi : lot conforme.' : 'Contrôle terminé : '.count($issues).' écart(s).');
    }

    public function destroy(Request $request, ProductSupplierLink $link): RedirectResponse
    {
        abort_unless($link->product->brand_id === $request->user()->brand?->id, 403);
        $link->delete();

        return back()->with('success', 'Lien produit ↔ lot retiré.');
    }
}
