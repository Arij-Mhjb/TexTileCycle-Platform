<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\RecyclingLoop;
use App\Models\RecyclingLoopItem;
use App\Services\RecyclingEstimator;
use App\Services\RecyclingFlowService;
use App\Services\RecyclingLoopService;
use App\Services\RecyclingPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Boucles de recyclage, vues par la marque (qui envoie), le fournisseur (qui recycle)
 * et l'admin (qui suit). Chacun ne voit que les boucles qui le concernent.
 */
class RecyclingLoopController extends Controller
{
    // ───────── Marque ─────────

    public function brandIndex(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return $this->listing('brand', RecyclingLoop::where('brand_id', $brand->id));
    }

    public function create(Request $request, RecyclingEstimator $estimator): View
    {
        $brand = $request->user()->brand()->firstOrFail();
        $materials = Material::orderBy('name')->get(['id', 'name']);
        $products = $brand->products()->with('materials')->orderBy('name')->get();

        return view('modules.recycling.create', [
            'suppliers' => $brand->suppliers()->whereNotNull('user_id')->orderBy('name')->get(),
            'products' => $products,
            'materials' => $materials,
            // Pour l'estimation en direct : matière d'origine → [matière recyclée, rendement].
            'routes' => $materials->mapWithKeys(fn (Material $material) => [$material->id => $estimator->route($material->name)]),
            'productMaterial' => $products->mapWithKeys(fn ($product) => [$product->id => $product->materials->sortByDesc(fn ($m) => (float) ($m->pivot->percentage ?? 0))->first()?->id]),
            'kindFactors' => $estimator->kindFactors(),
            'tariff' => RecyclingPricing::tariff(),
            'selectedSupplier' => $request->integer('supplier') ?: null,
        ]);
    }

    public function store(Request $request, RecyclingLoopService $loops): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('brand_id', $brand->id)],
            'mode' => ['required', Rule::in(array_keys(RecyclingLoop::MODES))],
            'brand_message' => 'nullable|string|max:1000',
            'wished_pickup_at' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1|max:10',
            'items.*.kind' => ['required', Rule::in(array_keys(RecyclingLoopItem::KINDS))],
            'items.*.label' => 'nullable|string|max:150',
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('brand_id', $brand->id)],
            'items.*.material_id' => 'nullable|exists:materials,id',
            'items.*.pieces' => 'nullable|integer|min:1|max:100000',
            'items.*.quantity_kg' => 'required|numeric|min:0.1|max:100000',
            'items.*.issue' => 'nullable|required_if:items.*.kind,defective|string|max:500',
            'items.*.photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'items.*.issue.required_if' => 'Décrivez le défaut des pièces.',
            'items.required' => 'Ajoutez au moins un lot d’articles.',
            'mode.required' => 'Choisissez ce que vous voulez en retour.',
        ], [
            'supplier_id' => 'fournisseur', 'mode' => 'retour souhaité', 'items.*.label' => 'désignation', 'items.*.quantity_kg' => 'poids', 'items.*.photo' => 'photo',
        ]);

        $photos = collect($request->file('items', []))->map(fn ($item) => $item['photo'] ?? null)->all();
        $loop = $loops->request($brand, $brand->suppliers()->findOrFail($data['supplier_id']), array_values($data['items']), array_values($photos), $data, $request->user());

        return to_route('brand.recycling.show', $loop)->with('success', 'Demande '.$loop->reference.' envoyée à '.$loop->supplier->name.'.');
    }

    public function accept(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $loops->accept($recyclingLoop);

        return back()->with('success', 'Proposition acceptée. Préparez l’envoi des articles.');
    }

    public function ship(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $loops->ship($recyclingLoop);

        return back()->with('success', 'Envoi confirmé : le fournisseur est prévenu.');
    }

    public function close(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $loops->close($recyclingLoop);

        return back()->with('success', $recyclingLoop->isBuyback() ? 'C’est terminé : l’avoir est confirmé.' : 'C’est terminé : la matière recyclée est dans vos lots.');
    }

    // ───────── Fournisseur ─────────

    public function supplierIndex(Request $request): View
    {
        return $this->listing('supplier', RecyclingLoop::whereHas('supplier', fn (Builder $query) => $query->where('user_id', $request->user()->id)));
    }

    public function propose(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $data = $request->validate([
            'output_material_id' => 'required|exists:materials,id',
            'expected_output_kg' => 'required|numeric|min:0.1',
            'ready_by' => 'nullable|date|after_or_equal:today',
            'supplier_message' => 'nullable|string|max:1000',
        ], [], ['output_material_id' => 'matière produite', 'expected_output_kg' => 'quantité prévue', 'ready_by' => 'date']);
        $recyclingLoop->load('items');
        if ((float) $data['expected_output_kg'] > $recyclingLoop->totalKg()) {
            return back()->withErrors(['expected_output_kg' => 'On ne peut pas produire plus que les '.number_format($recyclingLoop->totalKg(), 2, ',', ' ').' kg envoyés.'])->withInput();
        }
        $loops->propose($recyclingLoop, $data);

        return back()->with('success', 'Proposition envoyée à la marque, avec le prix calculé automatiquement.');
    }

    public function receive(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $data = $request->validate(['received_kg' => 'required|numeric|min:0.1'], [], ['received_kg' => 'poids pesé']);
        $loops->receive($recyclingLoop, (float) $data['received_kg']);

        return back()->with('success', 'Réception enregistrée.');
    }

    public function transform(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $data = $request->validate([
            'output_kg' => 'required|numeric|min:0.1',
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], ['proof.required' => 'Joignez une preuve (photo de la matière produite, bon de production…).'], ['output_kg' => 'matière produite', 'proof' => 'preuve']);
        $loops->transform($recyclingLoop, (float) $data['output_kg'], $request->file('proof'));

        return back()->with('success', 'Production déclarée. Livrez maintenant la matière recyclée.');
    }

    public function deliver(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $loops->deliver($recyclingLoop);

        return back()->with('success', $recyclingLoop->isBuyback() ? 'Avoir versé à la marque.' : 'Matière recyclée livrée : un nouveau lot traçable a été créé.');
    }

    // ───────── Commun ─────────

    public function decline(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): RedirectResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $data = $request->validate(['decline_reason' => 'required|string|min:5|max:500'], [], ['decline_reason' => 'motif']);
        $loops->decline($recyclingLoop, $data['decline_reason'], $request->user()->role === 'supplier' ? 'supplier' : 'brand');

        return back()->with('success', 'Refus enregistré.');
    }

    /** L'admin voit les flux : qui envoie quoi à qui, ce que ça devient, et où les demandes bloquent. */
    public function adminIndex(RecyclingFlowService $flows): View
    {
        $loops = RecyclingLoop::with(['brand', 'supplier', 'items', 'outputMaterial'])->latest()->take(500)->get();
        $closed = $loops->where('status', 'closed');
        $active = $loops->where('status', '!=', 'declined');

        return view('modules.recycling.admin', [
            'loops' => $loops,
            'sankey' => $flows->sankey($loops),
            'funnel' => $flows->funnel($loops),
            'stalled' => $loops->filter(fn (RecyclingLoop $loop) => $loop->isOpen() && $loop->updated_at->lt(now()->subDays(5)))->sortBy('updated_at')->take(6),
            'stats' => [
                'loops' => $loops->count(),
                'open' => $loops->filter->isOpen()->count(),
                'brands' => $active->pluck('brand_id')->unique()->count(),
                'suppliers' => $active->pluck('supplier_id')->unique()->count(),
                'sent_kg' => $loops->whereNotIn('status', ['requested', 'proposed', 'accepted', 'declined'])->sum(fn ($loop) => (float) ($loop->received_kg ?: $loop->totalKg())),
                'recycled_kg' => (float) $loops->whereIn('status', ['transformed', 'delivered', 'closed'])->sum('output_kg'),
                'yield' => $closed->sum('received_kg') > 0 ? (int) round($closed->sum('output_kg') / $closed->sum('received_kg') * 100) : null,
                'fees' => (float) $closed->where('mode', 'recover')->sum('amount'),
                'credits' => abs((float) $closed->where('mode', 'buyback')->sum('amount')),
            ],
        ]);
    }

    public function show(Request $request, RecyclingLoop $recyclingLoop, RecyclingLoopService $loops): View
    {
        $this->authorizeFor($request, $recyclingLoop);
        $recyclingLoop->load(['brand', 'supplier', 'requester', 'items.product.materials', 'items.material', 'outputMaterial', 'outputBatch']);

        return view('modules.recycling.show', [
            'cycle' => $recyclingLoop,
            'space' => $this->space($request),
            'estimate' => $loops->estimate($recyclingLoop),
            'materials' => Material::orderBy('name')->get(['id', 'name']),
            'tariff' => RecyclingPricing::tariff(),
        ]);
    }

    public function document(Request $request, RecyclingLoop $recyclingLoop, string $type, ?int $item = null): StreamedResponse
    {
        $this->authorizeFor($request, $recyclingLoop);
        $path = $type === 'proof' ? $recyclingLoop->proof_path : $recyclingLoop->items()->whereKey($item)->value('photo_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function listing(string $space, Builder $query): View
    {
        $loops = $query->with(['brand', 'supplier', 'items', 'outputMaterial'])->latest()->take(200)->get();
        $closed = $loops->where('status', 'closed');

        return view('modules.recycling.index', [
            'space' => $space,
            'todo' => $loops->filter(fn (RecyclingLoop $loop) => $loop->nextActor() === $space),
            'ongoing' => $loops->filter(fn (RecyclingLoop $loop) => $loop->isOpen() && $loop->nextActor() !== $space),
            'done' => $loops->reject(fn (RecyclingLoop $loop) => $loop->isOpen()),
            'stats' => [
                'sent_kg' => $loops->whereNotIn('status', ['requested', 'proposed', 'accepted', 'declined'])->sum(fn ($loop) => (float) ($loop->received_kg ?: $loop->totalKg())),
                'recycled_kg' => (float) $loops->whereIn('status', ['transformed', 'delivered', 'closed'])->sum('output_kg'),
                'yield' => $closed->sum('received_kg') > 0 ? (int) round($closed->sum('output_kg') / $closed->sum('received_kg') * 100) : null,
                'open' => $loops->filter->isOpen()->count(),
            ],
        ]);
    }

    private function space(Request $request): string
    {
        return ['brand' => 'brand', 'supplier' => 'supplier'][$request->user()->role] ?? 'admin';
    }

    private function authorizeFor(Request $request, RecyclingLoop $loop): void
    {
        $user = $request->user();
        $allowed = match ($user->role) {
            'admin' => true,
            'brand' => $loop->brand_id === $user->brand?->id,
            'supplier' => $loop->supplier?->user_id === $user->id,
            default => false,
        };
        abort_unless($allowed, 403);
        // Les actions d'un rôle ne sont ouvertes qu'à ce rôle (l'admin observe).
        abort_if($user->role === 'admin' && $request->isMethod('post'), 403);
    }
}
