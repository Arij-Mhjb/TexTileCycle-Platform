<?php

namespace App\Http\Controllers;

use App\Models\MaterialMatch;
use App\Services\MaterialExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandMaterialMatchController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return view('brand.material-matches', [
            'matches' => MaterialMatch::with([
                'request', 'productReturn.product.materials', 'productReturn.scan.assets',
                'productReturn.collectionPoint',
            ])->whereHas('request', fn ($query) => $query->where('brand_id', $brand->id))
                ->latest()
                ->paginate(15),
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
