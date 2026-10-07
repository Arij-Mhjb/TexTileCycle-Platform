<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CollectionPoint;
use App\Models\PassportProposal;
use App\Models\Product;
use App\Services\PassportProposalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPassportProposalController extends Controller
{
    public function index(Request $request): View
    {
        $query = PassportProposal::with(['consumer', 'brand', 'product', 'scan.assets'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.passport-proposals.index', ['proposals' => $query->paginate(20)->withQueryString()]);
    }

    public function show(PassportProposal $passportProposal): View
    {
        return view('admin.passport-proposals.show', [
            'proposal' => $passportProposal->load(['consumer', 'brand', 'product', 'scan.assets', 'events.actor', 'collectionPoints.recoveryProgram']),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'collectionPoints' => CollectionPoint::where('is_active', true)
                ->whereHas('recoveryProgram', fn ($program) => $program->where('is_active', true))
                ->with('recoveryProgram')->orderBy('city')->orderBy('name')->get(),
        ]);
    }

    public function review(
        Request $request,
        PassportProposal $passportProposal,
        PassportProposalWorkflowService $workflow
    ): RedirectResponse {
        $data = $this->normalize($request->validate($this->rules()));
        $workflow->adminReview($passportProposal, $request->user(), $data['decision'], $data);

        return to_route('admin.passport-proposals.show', $passportProposal)->with('success', 'Décision enregistrée et historisée.');
    }

    public function updateCollectionPoints(
        Request $request,
        PassportProposal $passportProposal,
        PassportProposalWorkflowService $workflow
    ): RedirectResponse {
        $data = $request->validate([
            'collection_point_ids' => 'required|array|min:1',
            'collection_point_ids.*' => 'integer|distinct|exists:collection_points,id',
        ]);
        $workflow->assignCollectionPoints($passportProposal, $request->user(), $data['collection_point_ids']);

        return to_route('admin.passport-proposals.show', $passportProposal)->with('success', 'Points partenaires autorisés mis à jour.');
    }

    private function rules(): array
    {
        return [
            'decision' => 'required|in:approve,request_information,reject',
            'brand_id' => 'nullable|exists:brands,id',
            'product_id' => 'nullable|exists:products,id',
            'note' => 'nullable|string|max:3000',
            'brand' => 'nullable|string|max:255', 'product_name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255', 'barcode' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255', 'color' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:100', 'manufacturing_country' => 'nullable|string|max:150',
            'retailer' => 'nullable|string|max:255', 'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0', 'currency' => 'nullable|string|size:3',
            'condition' => 'nullable|in:good,wearable,minor_damage,damaged,unusable,unknown',
            'composition_text' => 'nullable|string|max:3000',
            'defects_text' => 'nullable|string|max:2000',
            'collection_point_ids' => 'required_if:decision,approve|array|min:1',
            'collection_point_ids.*' => 'integer|distinct|exists:collection_points,id',
        ];
    }

    private function normalize(array $data): array
    {
        if (array_key_exists('composition_text', $data)) {
            $data['composition'] = collect(preg_split('/\r\n|\r|\n/', $data['composition_text'] ?? ''))->filter()->map(function ($line) {
                [$material, $percentage] = array_pad(explode(':', $line, 2), 2, null);
                return ['material' => trim($material), 'percentage' => $percentage !== null ? (float) trim($percentage) : null];
            })->values()->all();
        }
        if (array_key_exists('defects_text', $data)) {
            $data['defects'] = collect(preg_split('/\r\n|\r|\n/', $data['defects_text'] ?? ''))->filter()->values()->all();
        }
        unset($data['composition_text'], $data['defects_text']);

        return $data;
    }
}
