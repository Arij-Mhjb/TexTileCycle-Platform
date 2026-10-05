<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\View\View;

class PublicBrandController extends Controller
{
    public function index(): View
    {
        return view('brands.index', ['brands' => Brand::where('is_active', true)->withCount('products')->paginate(12)]);
    }

    public function show(Brand $brand): View
    {
        abort_unless($brand->is_active, 404);
        $brand->load(['products' => fn ($q) => $q->where('is_active', true)->with('materials')]);
        $assessments = $brand->user->assessments()->with('label.steps', 'evidences')->get();
        return view('brands.show', compact('brand', 'assessments'));
    }
}
