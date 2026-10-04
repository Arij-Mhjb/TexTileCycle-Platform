<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RecoveryProgram;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $returns = ProductReturn::query()
            ->where('user_id', $user->id)
            ->with(['product', 'collectionPoint.recoveryProgram', 'treatmentResult'])
            ->latest()
            ->get();

        return view('consumer.home', [
            'personalStats' => [
                'returns' => $returns->count(),
                'weight' => round((float) $returns->sum('weight_kg'), 2),
                'scans' => Scan::where('user_id', $user->id)->count(),
                'points' => $returns->sum(fn ($return) => $return->collectionPoint?->recoveryProgram?->reward_points ?? 0),
            ],
            'recentReturns' => $returns->take(4),
            'featuredProducts' => Product::where('status', 'published')->latest()->limit(6)->get(),
            'programs' => RecoveryProgram::where('is_active', true)->withCount('collectionPoints')->latest()->limit(4)->get(),
            'nearbyPoints' => CollectionPoint::where('is_active', true)->with('recoveryProgram')->latest()->limit(4)->get(),
        ]);
    }
}
