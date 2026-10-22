<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Services\Ai\GreenClaimsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Anti-greenwashing : analyse des descriptions produits et de tout texte marketing (Impact ESG). */
class GreenClaimsController extends Controller
{
    public function index(Request $request, GreenClaimsService $claims): View
    {
        $brand = $request->user()->brand()->firstOrFail();
        $text = (string) $request->input('texte', '');
        $products = $brand->products()->whereNotNull('description')->orderBy('name')->get(['id', 'name', 'category', 'description']);

        return view('modules.esg.claims', [
            'brand' => $brand,
            'text' => $text,
            'analysis' => trim($text) !== '' ? $claims->analyse($text) : null,
            'products' => $products->map(fn ($product) => ['product' => $product, 'analysis' => $claims->analyse((string) $product->description)]),
        ]);
    }

    public function analyse(Request $request, GreenClaimsService $claims): View
    {
        $request->validate(['texte' => 'required|string|min:5|max:3000'], [], ['texte' => 'texte']);

        return $this->index($request, $claims);
    }
}
