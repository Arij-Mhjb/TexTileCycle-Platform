<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $brand = $request->user()->brand()->withCount('products')->firstOrFail();
        $assessments = $request->user()->assessments()->with('label')->latest()->get();
        return view('brand.dashboard', [
            'brand' => $brand,
            'assessments' => $assessments,
            'publishedProducts' => $brand->products()->where('is_active', true)->count(),
            'verifiedEvidence' => $request->user()->assessments()->withCount(['evidences as approved_count' => fn ($q) => $q->where('status', 'approved')])->get()->sum('approved_count'),
        ]);
    }
}
