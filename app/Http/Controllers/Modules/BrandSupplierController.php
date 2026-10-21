<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Notifications\SupplierInvitationMail;
use App\Services\Sms\SmsService;
use App\Services\SupplierAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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

        return view('modules.suppliers.brand.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request, SupplierAccountService $accounts): RedirectResponse
    {
        $brand = $this->brand($request);
        // Nouveau fournisseur : « En attente d'activation » jusqu'à ce qu'il active son compte.
        $supplier = $brand->suppliers()->create([...$this->validated($request, $brand), 'status' => 'pending']);
        $redirect = to_route('brand.suppliers.show', $supplier)->with('success', 'Fournisseur ajouté.');

        return $request->boolean('create_access') ? $this->withAccess($redirect, $supplier, $accounts) : $redirect;
    }

    public function show(Request $request, Supplier $supplier): View
    {
        $this->authorizeSupplier($request, $supplier);
        $supplier->load(['user', 'smsMessages', 'latestInvitation', 'purchaseOrders.lines.batches', 'purchaseOrders.lines.material', 'batches.material', 'batches.certificates', 'batches.purchaseOrder', 'batches.productLinks.product']);

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
     * Invitation par lien d'activation (usage unique, 72 h) : la marque transmet le lien,
     * le fournisseur choisit lui-même son mot de passe. Si un fournisseur SMS réel est configuré
     * (SMS_DRIVER), le lien part aussi automatiquement par SMS ; l'historique ne garde pas le lien secret.
     */
    private function withAccess(RedirectResponse $redirect, Supplier $supplier, SupplierAccountService $accounts): RedirectResponse
    {
        try {
            $invitation = $accounts->invite($supplier, auth()->user());
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors());
        }

        $message = $accounts->invitationMessage($supplier, $invitation);
        // E-mail au fournisseur avec le lien d'activation. Un problème d'envoi (SMTP) n'empêche pas l'invitation.
        $mail = ['to' => $invitation['email'], 'sent' => true, 'error' => null, 'driver' => (string) config('mail.default')];
        try {
            Notification::route('mail', $invitation['email'])->notify(new SupplierInvitationMail(
                $supplier->brand?->name ?? 'Votre client', $supplier->contact_name ?: $supplier->name, $invitation['url'], $invitation['expires_at']->format('d/m/Y à H:i'),
            ));
        } catch (Throwable $exception) {
            report($exception);
            $mail = ['sent' => false, 'error' => Str::limit($exception->getMessage(), 160)] + $mail;
        }
        $sms = null;
        if ($supplier->phone && config('sms.driver') !== 'log') {
            $sms = app(SmsService::class)->send($supplier->phone, $message, $supplier->id, str_replace($invitation['url'], '[lien d’activation]', $message));
        }

        return $redirect->with('supplier_invitation', [
            'supplier' => $supplier->name,
            'email' => $invitation['email'],
            'url' => $invitation['url'],
            'expires_at' => $invitation['expires_at']->format('d/m/Y à H:i'),
            'message' => $message,
            'phone' => $supplier->phone,
            'sms' => $sms ? ['to' => $sms->to, 'status' => $sms->status, 'error' => $sms->error] : null,
            'mail' => $mail,
        ])->with('success', $mail['sent'] ? 'Invitation envoyée par e-mail à '.$invitation['email'].'.' : 'Invitation créée : transmettez le lien d’activation à '.$supplier->name.'.');
    }

    /** Suspendre / réactiver : plus de nouvelles commandes, mais l'historique et les preuves sont conservés. */
    public function suspend(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeSupplier($request, $supplier);
        $suspended = ! $supplier->isSuspended();
        if ($suspended) {
            $supplier->update(['status' => 'suspended']);
        } else {
            $supplier->update(['status' => 'pending']);
            $supplier->syncAccountStatus(); // redevient « Actif » s'il a déjà activé son compte
        }

        return back()->with('success', $suspended
            ? 'Fournisseur suspendu : plus de nouvelles commandes, son historique et ses preuves sont conservés.'
            : 'Fournisseur réactivé.');
    }

    private function validated(Request $request, Brand $brand, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('suppliers')->where('brand_id', $brand->id)->ignore($supplier?->id)],
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:40',
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [
            'name.unique' => 'Vous avez déjà un fournisseur à ce nom.',
        ], [
            'name' => 'nom et prénom', 'email' => 'e-mail', 'phone' => 'téléphone',
            'country' => 'pays', 'city' => 'ville', 'address' => 'adresse', 'notes' => 'notes',
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
