<?php

namespace App\Http\Controllers;

use App\Models\PassportProposal;
use App\Services\PassportProposalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandPassportProposalController extends Controller
{
    public function index(Request $request): View
    {
        return view('brand.passport-proposals.index', [
            'proposals' => $request->user()->brand->passportProposals()
                ->with(['product', 'scan'])
                ->whereIn('status', ['brand_pending', 'ready_for_return'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(Request $request, PassportProposal $passportProposal): View
    {
        $this->own($request, $passportProposal);

        return view('brand.passport-proposals.show', [
            'proposal' => $passportProposal->load([
                'product.materials',
                'events.actor',
                'scan.assets' => fn ($query) => $query->where('type', '!=', 'receipt'),
            ]),
        ]);
    }

    public function review(
        Request $request,
        PassportProposal $passportProposal,
        PassportProposalWorkflowService $workflow
    ): RedirectResponse {
        $this->own($request, $passportProposal);
        $data = $request->validate([
            'decision' => 'required|in:confirm,correct,reject',
            'note' => 'nullable|required_if:decision,reject|string|max:3000',
            'brand' => 'nullable|string|max:255', 'product_name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255', 'barcode' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255', 'color' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:100', 'manufacturing_country' => 'nullable|string|max:150',
            'composition_text' => 'nullable|string|max:3000',
        ]);
        if (array_key_exists('composition_text', $data)) {
            $data['composition'] = collect(preg_split('/\r\n|\r|\n/', $data['composition_text'] ?? ''))->filter()->map(function ($line) {
                [$material, $percentage] = array_pad(explode(':', $line, 2), 2, null);
                return ['material' => trim($material), 'percentage' => $percentage !== null ? (float) trim($percentage) : null];
            })->values()->all();
            unset($data['composition_text']);
        }
        $workflow->brandReview($passportProposal, $request->user(), $data['decision'], $data);

        return to_route('brand.passport-proposals.index')->with('success', 'Réponse transmise à TexTileCycle et au consommateur.');
    }

    private function own(Request $request, PassportProposal $proposal): void
    {
        abort_unless($request->user()->brand?->id === $proposal->brand_id, 403);
    }
}
