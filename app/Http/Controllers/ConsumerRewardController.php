<?php

namespace App\Http\Controllers;

use App\Models\ConsumerNotification;
use App\Models\RewardItem;
use App\Models\RewardRedemption;
use App\Models\RewardTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsumerRewardController extends Controller
{
    public function index(Request $request): View
    {
        return view('consumer.rewards', [
            'items' => RewardItem::where('is_active', true)->orderBy('points_cost')->get()->filter->isAvailable(),
            'redemptions' => $request->user()->rewardRedemptions()->with('item')->latest()->paginate(10),
            'balance' => $this->balance($request),
        ]);
    }

    public function redeem(Request $request, RewardItem $rewardItem): RedirectResponse
    {
        DB::transaction(function () use ($request, $rewardItem) {
            $item = RewardItem::whereKey($rewardItem->id)->lockForUpdate()->firstOrFail();
            if (! $item->isAvailable()) {
                throw ValidationException::withMessages(['reward' => 'Cette récompense n’est plus disponible.']);
            }

            $transactions = RewardTransaction::where('user_id', $request->user()->id)->lockForUpdate()->get();
            $balance = (int) $transactions->whereIn('status', ['validated', 'spent'])->sum('points');
            if ($balance < $item->points_cost) {
                throw ValidationException::withMessages(['reward' => 'Votre solde de points est insuffisant.']);
            }

            $redemption = RewardRedemption::create([
                'user_id' => $request->user()->id,
                'reward_item_id' => $item->id,
                'reference' => 'RWD-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'code' => Str::upper(Str::random(10)),
                'points_spent' => $item->points_cost,
                'status' => 'issued',
                'expires_at' => now()->addMonths(3),
            ]);
            RewardTransaction::create([
                'user_id' => $request->user()->id, 'type' => 'reward_redemption',
                'points' => -$item->points_cost, 'status' => 'spent',
                'description' => 'Récompense '.$item->name.' · '.$redemption->reference,
                'validated_at' => now(),
            ]);
            if ($item->stock !== null) $item->decrement('stock');
            ConsumerNotification::create([
                'user_id' => $request->user()->id, 'type' => 'reward_issued', 'title' => 'Récompense disponible',
                'message' => $item->name.' : présentez le code '.$redemption->code.'.', 'url' => route('consumer.rewards.index'),
            ]);
        });

        return back()->with('success', 'Récompense générée. Son code est disponible ci-dessous.');
    }

    private function balance(Request $request): int
    {
        return (int) $request->user()->rewardTransactions()->whereIn('status', ['validated', 'spent'])->sum('points');
    }
}
