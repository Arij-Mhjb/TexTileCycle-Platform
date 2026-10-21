<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Product;
use App\Models\RecoveryProgram;
use App\Services\CircularRecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerExploreController extends Controller
{
    public function products(Request $request): View
    {
        $query = Product::query()->where('status', 'published')->where('is_active', true)->with(['materials', 'brandProfile']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) $query->where('category', $request->string('category'));
        if ($request->filled('origin')) $query->where('origin', 'like', '%'.$request->string('origin').'%');
        if ($request->boolean('recyclable')) $query->where('recyclable', true);
        if ($request->boolean('reusable')) $query->where('reusable', true);
        if ($request->boolean('repairable')) $query->where('repairable', true);
        if ($request->filled('score')) $query->where('recyclability_score', '>=', (int) $request->input('score'));

        return view('consumer.products', [
            'products' => $query->latest()->paginate(9)->withQueryString(),
            'categories' => Product::where('is_active', true)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function product(Product $product, CircularRecommendationService $recommendations): View
    {
        abort_unless($product->is_active && $product->status === 'published', 404);
        $product->load(['materials', 'brandProfile']);
        $recommendation = $recommendations->recommend($product);
        $points = CollectionPoint::where('is_active', true)
            ->whereHas('recoveryProgram', fn ($query) => $query->where('is_active', true)->where('type', $recommendation['action']))
            ->with('recoveryProgram')
            ->limit(4)
            ->get();

        return view('consumer.product-show', compact('product', 'recommendation', 'points'));
    }

    public function programs(Request $request): View
    {
        $query = RecoveryProgram::where('is_active', true)->withCount('collectionPoints');
        if ($request->filled('type')) $query->where('type', $request->string('type'));

        return view('consumer.programs', ['programs' => $query->latest()->paginate(9)->withQueryString()]);
    }

    public function points(Request $request): View
    {
        $coordinates = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        $query = CollectionPoint::where('is_active', true)->with('recoveryProgram');

        if ($request->filled('city')) $query->where('city', 'like', '%'.$request->string('city').'%');
        if ($request->filled('type')) {
            $query->whereHas('recoveryProgram', fn ($program) => $program->where('type', $request->string('type')));
        }

        $points = $query->latest()->paginate(50)->withQueryString();
        $latitude = isset($coordinates['latitude']) ? (float) $coordinates['latitude'] : null;
        $longitude = isset($coordinates['longitude']) ? (float) $coordinates['longitude'] : null;
        if ($latitude !== null && $longitude !== null) {
            $points->setCollection($points->getCollection()->map(function ($point) use ($latitude, $longitude) {
                $point->distance_km = ($point->latitude !== null && $point->longitude !== null)
                    ? $this->distance($latitude, $longitude, (float) $point->latitude, (float) $point->longitude)
                    : null;
                return $point;
            })->sortBy(fn ($point) => $point->distance_km ?? PHP_FLOAT_MAX)->values());
        }

        return view('consumer.points', compact('points', 'latitude', 'longitude'));
    }

    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
