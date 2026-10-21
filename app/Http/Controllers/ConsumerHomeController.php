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
            ->with(['product', 'collectionPoint.recoveryProgram', 'treatmentResult', 'rewardTransaction'])
            ->latest()
            ->get();

        $completed = $returns->whereIn('status', ['completed', 'processed']);
        $validatedPoints = (int) $user->rewardTransactions()->whereIn('status', ['validated', 'spent'])->sum('points');
        $pendingPoints = (int) $user->rewardTransactions()->where('status', 'pending')->sum('points');

        return view('consumer.home', [
            'personalStats' => [
                'returns' => $returns->count(),
                'weight' => round((float) $completed->sum(fn ($return) => $return->confirmed_weight_kg ?? $return->weight_kg), 2),
                'scans' => Scan::where('user_id', $user->id)->count(),
                'points' => $validatedPoints,
                'pending_points' => $pendingPoints,
                'completed' => $completed->count(),
            ],
            'recentReturns' => $returns->take(4),
            'featuredProducts' => Product::where('status', 'published')->latest()->limit(6)->get(),
            'programs' => RecoveryProgram::where('is_active', true)->withCount('collectionPoints')->latest()->limit(4)->get(),
            'nearbyPoints' => CollectionPoint::where('is_active', true)->with('recoveryProgram')->latest()->limit(4)->get(),
            'notifications' => $user->consumerNotifications()->whereNull('read_at')->latest()->limit(5)->get(),
        ]);
    }
}
