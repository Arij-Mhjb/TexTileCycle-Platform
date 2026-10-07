<?php

namespace App\Http\Controllers;

use App\Models\ProductLabelRecommendation;
use App\Services\ProductLabelAdvisor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductLabelRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->user()->brand;
        $recommendations = $brand->productLabelRecommendations()
            ->with('product.materials', 'label', 'roadmaps')->orderByDesc('readiness_score')->get()->groupBy('product_id');

        return view('brand.product-label-recommendations.index', [
            'products' => $brand->products()->with('materials')->orderBy('name')->get(),
            'recommendations' => $recommendations,
            'aiConfigured' => filled(config('product_label_ai.key')),
        ]);
    }

    public function analyse(Request $request, ProductLabelAdvisor $advisor): RedirectResponse
    {
        $results = $advisor->analyse($request->user()->brand);

        return back()->with('success', $results->count().' compatibilités produit-label ont été actualisées. Les résultats orientent la préparation sans valoir certification.');
    }

    public function assign(Request $request, ProductLabelRecommendation $recommendation): RedirectResponse
    {
        abort_unless($recommendation->brand_id === $request->user()->brand->id, 403);
        abort_if($recommendation->path === 'not_recommended', 422, 'Ce label ne correspond pas à la composition déclarée.');
        $recommendation->update(['status' => 'selected']);

        return to_route('brand.roadmaps.create', [
            'recommendation_id' => $recommendation->id,
            'label_id' => $recommendation->label_id,
            'product_id' => $recommendation->product_id,
        ]);
    }
}
