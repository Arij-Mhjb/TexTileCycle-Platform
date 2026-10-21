<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\SupplierBatch;
use App\Models\SupplierReturn;
use App\Services\SupplierReturnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Seconde vie de la matière (retours marque → fournisseur), pour les trois acteurs :
 * la marque déclare et renvoie, le fournisseur reprend et valorise, l'admin vérifie.
 * Chacun ne voit que les retours qui le concernent.
 */
class SupplierReturnController extends Controller
{
    // ───────── Marque ─────────

    public function brandIndex(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return $this->listing('brand', SupplierReturn::where('brand_id', $brand->id), $request);
    }

    public function create(Request $request, SupplierReturnService $returns): View
    {
        $brand = $request->user()->brand()->firstOrFail();
        $batch = SupplierBatch::with(['supplier', 'material', 'purchaseOrder'])->findOrFail($request->integer('batch'));
        abort_unless($batch->supplier?->brand_id === $brand->id, 403);

        return view('modules.supplier-returns.create', ['batch' => $batch, 'returnable' => $returns->returnableKg($batch)]);
    }

    public function store(Request $request, SupplierReturnService $returns): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'supplier_batch_id' => 'required|integer|exists:supplier_batches,id',
            'quantity_kg' => 'required|numeric|min:0.1|max:100000',
            'reason' => ['required', Rule::in(array_keys(SupplierReturn::REASONS))],
            'description' => 'nullable|required_if:reason,defect|string|max:1000',
            'photo' => 'nullable|required_if:reason,defect|image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'description.required_if' => 'Décrivez le défaut constaté.',
            'photo.required_if' => 'Une photo du défaut est obligatoire.',
        ], ['quantity_kg' => 'quantité', 'reason' => 'motif', 'photo' => 'photo']);

        $return = $returns->declare($brand, SupplierBatch::findOrFail($data['supplier_batch_id']), $data, $request->file('photo'), $request->user());

        return to_route('brand.supplier-returns.show', $return)->with('success', 'Retour '.$return->reference.' envoyé au fournisseur.');
    }

    public function ship(Request $request, SupplierReturn $supplierReturn, SupplierReturnService $returns): RedirectResponse
    {
        $this->authorizeFor($request, $supplierReturn);
        $returns->ship($supplierReturn);

        return back()->with('success', 'Renvoi confirmé : le fournisseur est prévenu.');
    }

    // ───────── Fournisseur ─────────

    public function supplierIndex(Request $request): View
    {
        return $this->listing('supplier', SupplierReturn::whereHas('supplier', fn (Builder $query) => $query->where('user_id', $request->user()->id)), $request);
    }

    public function accept(Request $request, SupplierReturn $supplierReturn, SupplierReturnService $returns): RedirectResponse
    {
        $this->authorizeFor($request, $supplierReturn);
        $data = $request->validate([
            'destination' => ['required', Rule::in(array_keys(SupplierReturn::DESTINATIONS))],
            'pickup_at' => 'nullable|date|after_or_equal:today',
            'supplier_note' => 'nullable|string|max:1000',
        ], [], ['destination' => 'seconde vie prévue', 'pickup_at' => 'date d’enlèvement']);
        $returns->accept($supplierReturn, $data);

        return back()->with('success', 'Reprise acceptée : la marque peut renvoyer la matière.');
    }

    public function receive(Request $request, SupplierReturn $supplierReturn, SupplierReturnService $returns): RedirectResponse
    {
        $this->authorizeFor($request, $supplierReturn);
        $returns->receive($supplierReturn);

        return back()->with('success', 'Réception confirmée. Déclarez maintenant la seconde vie de la matière.');
    }

    public function complete(Request $request, SupplierReturn $supplierReturn, SupplierReturnService $returns): RedirectResponse
    {
        $this->authorizeFor($request, $supplierReturn);
        $data = $request->validate([
            'resold_kg' => 'required|numeric|min:0',
            'recycled_kg' => 'required|numeric|min:0',
            'upcycled_kg' => 'required|numeric|min:0',
            'waste_kg' => 'required|numeric|min:0',
            'supplier_note' => 'nullable|string|max:1000',
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], ['proof.required' => 'Joignez une preuve (bon de recyclage, facture de revente, photo…).'], [
            'resold_kg' => 'kg revendus', 'recycled_kg' => 'kg recyclés', 'upcycled_kg' => 'kg surcyclés', 'waste_kg' => 'kg non valorisés', 'proof' => 'preuve',
        ]);
        $returns->complete($supplierReturn, $data, $request->file('proof'));

        return back()->with('success', 'Seconde vie déclarée : TexTileCycle va vérifier la preuve.');
    }

    // ───────── Admin ─────────

    public function adminIndex(Request $request): View
    {
        return $this->listing('admin', SupplierReturn::query(), $request);
    }

    public function verify(Request $request, SupplierReturn $supplierReturn, SupplierReturnService $returns): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:approve,correction', 'admin_note' => 'nullable|string|max:1000']);
        $returns->verify($supplierReturn, $request->user(), $data['decision'] === 'approve', $data['admin_note'] ?? null);

        return back()->with('success', $data['decision'] === 'approve' ? 'Seconde vie vérifiée.' : 'Correction demandée au fournisseur.');
    }

    // ───────── Commun ─────────

    public function show(Request $request, SupplierReturn $supplierReturn): View
    {
        $this->authorizeFor($request, $supplierReturn);
        $supplierReturn->load(['brand', 'supplier', 'batch', 'order', 'material', 'requester', 'verifier']);

        return view('modules.supplier-returns.show', ['return' => $supplierReturn, 'space' => $this->space($request)]);
    }

    public function document(Request $request, SupplierReturn $supplierReturn, string $type): StreamedResponse
    {
        $this->authorizeFor($request, $supplierReturn);
        $path = ['photo' => $supplierReturn->photo_path, 'proof' => $supplierReturn->proof_path][$type] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function listing(string $space, Builder $query, Request $request): View
    {
        $all = (clone $query)->get(['status', 'quantity_kg', 'resold_kg', 'recycled_kg', 'upcycled_kg', 'waste_kg']);
        $done = $all->whereIn('status', ['completed', 'verified']);

        return view('modules.supplier-returns.index', [
            'space' => $space,
            'returns' => $query->with(['brand', 'supplier', 'material'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->orderByRaw("case status when 'requested' then 0 when 'correction' then 0 when 'completed' then 1 when 'accepted' then 2 when 'in_transit' then 2 when 'received' then 2 else 3 end")
                ->latest()->paginate(15)->withQueryString(),
            'stats' => [
                'count' => $all->count(),
                'open' => $all->whereNotIn('status', ['verified'])->count(),
                'returned_kg' => (float) $all->sum('quantity_kg'),
                'saved_kg' => (float) $done->sum(fn ($row) => $row->savedKg()),
                'saved_rate' => $done->sum('quantity_kg') > 0 ? (int) round($done->sum(fn ($row) => $row->savedKg()) / $done->sum('quantity_kg') * 100) : null,
            ],
        ]);
    }

    private function space(Request $request): string
    {
        return ['brand' => 'brand', 'supplier' => 'supplier'][$request->user()->role] ?? 'admin';
    }

    private function authorizeFor(Request $request, SupplierReturn $return): void
    {
        $user = $request->user();
        $allowed = match ($user->role) {
            'admin' => true,
            'brand' => $return->brand_id === $user->brand?->id,
            'supplier' => $return->supplier?->user_id === $user->id,
            default => false,
        };
        abort_unless($allowed, 403);
    }
}
