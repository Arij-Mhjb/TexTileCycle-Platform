<?php

namespace App\Http\Controllers;

use App\Models\MaterialMatch;
use App\Services\MaterialExchangeService;
use App\Services\MaterialMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandMaterialMatchController extends Controller
{
    public function index(Request $request, MaterialMatchingService $matching): View
    {
        $brand = $request->user()->brand()->firstOrFail();
        $verdict = in_array($request->query('verdict'), ['compatible', 'partial', 'incompatible'], true)
            ? $request->query('verdict')
            : null;
        $base = MaterialMatch::query()
            ->whereHas('request', fn ($query) => $query->where('brand_id', $brand->id));

        // Rattrapage unique pour les demandes créées avant l'ajout de l'analyse IA.
        if ((clone $base)->doesntExist()) {
            $brand->materialRequests()
                ->where('status', 'open')
                ->where('remaining_kg', '>', 0)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->get()
                ->each(fn ($materialRequest) => $matching->matchRequest($materialRequest));
        }

        return view('brand.material-matches', [
            'matches' => (clone $base)->with([
                'request', 'productReturn.product.materials', 'productReturn.scan.assets',
                'productReturn.collectionPoint',
            ])->when($verdict, fn ($query) => $query->where('ai_verdict', $verdict))
                ->orderByRaw("CASE ai_verdict WHEN 'compatible' THEN 1 WHEN 'partial' THEN 2 ELSE 3 END")
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'verdict' => $verdict,
            'counts' => [
                'all' => (clone $base)->count(),
                'compatible' => (clone $base)->where('ai_verdict', 'compatible')->count(),
                'partial' => (clone $base)->where('ai_verdict', 'partial')->count(),
                'incompatible' => (clone $base)->where('ai_verdict', 'incompatible')->count(),
            ],
        ]);
    }

    public function accept(Request $request, MaterialMatch $materialMatch, MaterialExchangeService $exchange): RedirectResponse
    {
        $this->guard($request, $materialMatch);
        $exchange->accept($materialMatch);

        return back()->with('success', 'Matière réservée. Le consommateur a reçu les instructions.');
    }

    public function receive(Request $request, MaterialMatch $materialMatch, MaterialExchangeService $exchange): RedirectResponse
    {
        $this->guard($request, $materialMatch);
        $exchange->receive($materialMatch);

        return back()->with('success', 'Réception confirmée. Vous pouvez effectuer le contrôle final.');
    }

    public function verify(Request $request, MaterialMatch $materialMatch, MaterialExchangeService $exchange): RedirectResponse
    {
        $this->guard($request, $materialMatch);
        $data = $request->validate(['note' => 'nullable|string|max:2000']);
        $exchange->verify($materialMatch, $data['note'] ?? null);

        return back()->with('success', 'Matière validée et points attribués.');
    }

    public function reject(Request $request, MaterialMatch $materialMatch, MaterialExchangeService $exchange): RedirectResponse
    {
        $this->guard($request, $materialMatch);
        $data = $request->validate(['note' => 'required|string|max:2000']);
        $exchange->reject($materialMatch, $data['note']);

        return back()->with('success', 'Proposition refusée avec motif.');
    }

    private function guard(Request $request, MaterialMatch $match): void
    {
        abort_unless($match->request()->where('brand_id', $request->user()->brand?->id)->exists(), 403);
    }
}
