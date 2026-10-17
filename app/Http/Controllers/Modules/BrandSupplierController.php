<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandSupplierController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $this->brand($request);
        $suppliers = $brand->suppliers()
            ->withCount(['batches', 'purchaseOrders', 'batches as verified_batches_count' => fn ($query) => $query->where('status', 'verified')])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('country', 'like', '%'.$request->search.'%')))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('modules.suppliers.brand.index', [
            'brand' => $brand,
            'suppliers' => $suppliers,
            'stats' => [
                'suppliers' => $brand->suppliers()->count(),
                'batches' => SupplierBatch::whereIn('supplier_id', $brand->suppliers()->pluck('id'))->count(),
                'verified' => SupplierBatch::whereIn('supplier_id', $brand->suppliers()->pluck('id'))->where('status', 'verified')->count(),
                'score' => (int) round($brand->suppliers()->avg('compliance_score') ?? 0),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->brand($request);

        return view('modules.suppliers.brand.form', ['supplier' => new Supplier(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $brand = $this->brand($request);
        $supplier = $brand->suppliers()->create($this->validated($request, $brand));

        return to_route('brand.suppliers.show', $supplier)->with('success', 'Fournisseur ajouté. Déclarez maintenant ses lots et certificats.');
    }

    public function show(Request $request, Supplier $supplier): View
    {
        $this->authorizeSupplier($request, $supplier);
        $supplier->load(['purchaseOrders.batches', 'batches.material', 'batches.certificates', 'batches.purchaseOrder', 'batches.productLinks.product']);

        return view('modules.suppliers.brand.show', [
            'supplier' => $supplier,
            'materials' => Material::orderBy('name')->get(),
            'certificateTypes' => MaterialOriginCertificate::TYPES,
        ]);
    }

    public function edit(Request $request, Supplier $supplier): View
    {
        $this->authorizeSupplier($request, $supplier);

        return view('modules.suppliers.brand.form', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $brand = $this->authorizeSupplier($request, $supplier);
        $supplier->update($this->validated($request, $brand, $supplier));

        return to_route('brand.suppliers.show', $supplier)->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeSupplier($request, $supplier);
        $supplier->delete();

        return to_route('brand.suppliers.index')->with('success', 'Fournisseur supprimé avec ses lots et certificats.');
    }

    private function validated(Request $request, Brand $brand, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('suppliers')->where('brand_id', $brand->id)->ignore($supplier?->id)],
            'contact_name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:40',
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'certifications' => 'nullable|string|max:255',
            'status' => 'required|in:active,under_review,suspended',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function brand(Request $request): Brand
    {
        return $request->user()->brand()->firstOrFail();
    }

    private function authorizeSupplier(Request $request, Supplier $supplier): Brand
    {
        $brand = $this->brand($request);
        abort_unless($supplier->brand_id === $brand->id, 403);

        return $brand;
    }
}
