<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerWalletController extends Controller
{
    public function __invoke(Request $request): View
    {
        $transactions = $request->user()->rewardTransactions()
            ->with('productReturn.product')
            ->latest()
            ->paginate(15);

        return view('consumer.wallet', [
            'transactions' => $transactions,
            'balance' => (int) $request->user()->rewardTransactions()->whereIn('status', ['validated', 'spent'])->sum('points'),
            'pending' => (int) $request->user()->rewardTransactions()->where('status', 'pending')->sum('points'),
            'earned' => (int) $request->user()->rewardTransactions()->where('status', 'validated')->where('points', '>', 0)->sum('points'),
            'redemptions' => $request->user()->rewardRedemptions()->with('item')->latest()->limit(5)->get(),
        ]);
    }
}
