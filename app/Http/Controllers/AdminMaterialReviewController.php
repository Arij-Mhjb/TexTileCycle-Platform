<?php

namespace App\Http\Controllers;

use App\Models\ProductReturn;
use App\Services\MaterialExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMaterialReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.material-reviews', [
            'returns' => ProductReturn::with([
                'user', 'product.materials', 'scan.assets', 'collectionPoint', 'rewardTransaction',
            ])->whereIn('status', ['received', 'inspected'])
                ->latest('deposited_at')
                ->paginate(12),
        ]);
    }

    public function update(Request $request, ProductReturn $productReturn, MaterialExchangeService $exchange): RedirectResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:verified,rejected,needs_information',
            'condition' => 'required|in:good,wearable,minor_damage,damaged,unusable,unknown',
            'confirmed_weight_kg' => 'required|numeric|min:0.01|max:999999',
            'admin_review_note' => 'nullable|string|max:2000',
        ]);

        $exchange->adminReview($productReturn, $request->user(), $data['decision'], $data);

        return back()->with('success', 'Contrôle administratif enregistré.');
    }
}
