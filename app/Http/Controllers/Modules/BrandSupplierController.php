<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Services\Sms\SmsService;
use App\Services\SupplierAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function store(Request $request, SupplierAccountService $accounts): RedirectResponse
    {
        $brand = $this->brand($request);
        $supplier = $brand->suppliers()->create($this->validated($request, $brand));
        $redirect = to_route('brand.suppliers.show', $supplier)->with('success', 'Fournisseur ajouté.');

        return $request->boolean('create_access') ? $this->withAccess($redirect, $supplier, $accounts) : $redirect;
    }

    public function show(Request $request, Supplier $supplier): View
    {
        $this->authorizeSupplier($request, $supplier);
        $supplier->load(['user', 'smsMessages', 'purchaseOrders.batches', 'batches.material', 'batches.certificates', 'batches.purchaseOrder', 'batches.productLinks.product']);

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

    public function update(Request $request, Supplier $supplier, SupplierAccountService $accounts): RedirectResponse
    {
        $brand = $this->authorizeSupplier($request, $supplier);
        $supplier->update($this->validated($request, $brand, $supplier));
        $redirect = to_route('brand.suppliers.show', $supplier)->with('success', 'Fournisseur mis à jour.');

        return $request->boolean('create_access') && ! $supplier->user_id ? $this->withAccess($redirect, $supplier, $accounts) : $redirect;
    }

    /** Suppression autorisée seulement sans preuve de traçabilité (lot vérifié ou relié à un produit). */
    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeSupplier($request, $supplier);
        if (! $supplier->canBeDeleted()) {
            return back()->withErrors(['supplier' => 'Ce fournisseur a des lots vérifiés ou utilisés dans vos produits : ces preuves doivent être conservées. Suspendez-le plutôt.']);
        }
        $supplier->delete();

        return to_route('brand.suppliers.index')->with('success', 'Fournisseur supprimé.');
    }

    /** Crée l'accès fournisseur ou régénère un mot de passe provisoire. */
    public function access(Request $request, Supplier $supplier, SupplierAccountService $accounts): RedirectResponse
    {
        $this->authorizeSupplier($request, $supplier);
        abort_if($supplier->isSuspended(), 422, 'Fournisseur suspendu.');

        return $this->withAccess(back(), $supplier, $accounts);
    }

    /**
     * Le mot de passe provisoire n'est jamais montré à la marque :
     * il est envoyé uniquement par SMS au numéro du fournisseur (l'historique le garde masqué).
     */
    private function withAccess(RedirectResponse $redirect, Supplier $supplier, SupplierAccountService $accounts): RedirectResponse
    {
        try {
            $credentials = $accounts->grantAccess($supplier);
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors());
        }

        $sms = app(SmsService::class)->send(
            $supplier->phone,
            $accounts->smsText($supplier, $credentials),
            $supplier->id,
            $accounts->smsText($supplier, $credentials, masked: true),
        );

        return $redirect->with('supplier_access', [
            'supplier' => $supplier->name,
            'email' => $credentials['email'],
            'created' => $credentials['created'],
            'sms' => ['to' => $sms->to, 'driver' => $sms->driver, 'status' => $sms->status, 'error' => $sms->error],
        ])->with('success', $credentials['created'] ? 'Accès fournisseur créé.' : 'Nouveau mot de passe provisoire généré.');
    }

    /** Suspendre / réactiver : plus de nouvelles commandes, mais l'historique et les preuves sont conservés. */
    public function suspend(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeSupplier($request, $supplier);
        $suspended = ! $supplier->isSuspended();
        $supplier->update(['status' => $suspended ? 'suspended' : 'active']);

        return back()->with('success', $suspended
            ? 'Fournisseur suspendu : plus de nouvelles commandes, son historique et ses preuves sont conservés.'
            : 'Fournisseur réactivé.');
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
        ], [
            'name.unique' => 'Vous avez déjà un fournisseur avec ce nom.',
        ], [
            'name' => 'raison sociale', 'contact_name' => 'contact', 'email' => 'e-mail', 'phone' => 'téléphone',
            'country' => 'pays', 'city' => 'ville', 'address' => 'adresse', 'certifications' => 'certifications',
            'status' => 'statut', 'notes' => 'notes',
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
