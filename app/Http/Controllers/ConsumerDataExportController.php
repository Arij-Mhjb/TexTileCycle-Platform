<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsumerDataExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only(['id', 'name', 'email', 'role', 'profile_photo_path', 'created_at', 'updated_at']),
            'scans' => $user->scans()->with('product:id,name,brand,sku')->get()->toArray(),
            'returns' => $user->productReturns()->with(['product:id,name,brand,sku', 'collectionPoint:id,name,city'])->get()->toArray(),
            'repairs' => $user->repairOrders()->with(['product:id,name,brand,sku', 'repairCenter:id,name,city'])->get()->toArray(),
            'trade_ins' => $user->tradeInRequests()->with(['product:id,name,brand,sku', 'collectionPoint:id,name,city'])->get()->toArray(),
            'marketplace_orders' => $user->marketplaceOrders()->with('listing:id,title,price,currency')->get()->toArray(),
            'rewards' => [
                'transactions' => $user->rewardTransactions()->get()->toArray(),
                'redemptions' => $user->rewardRedemptions()->with('item:id,name,partner_name')->get()->toArray(),
            ],
            'claims' => $user->consumerClaims()->get()->toArray(),
            'community' => [
                'posts' => $user->communityPosts()->withCount(['likes', 'comments', 'reposts'])->get()->toArray(),
                'comments' => $user->communityComments()->get()->toArray(),
            ],
            'notifications' => $user->consumerNotifications()->get()->toArray(),
        ];

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
            'mes-donnees-textilecycle-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }
}
