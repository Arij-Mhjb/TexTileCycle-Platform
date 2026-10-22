<?php

namespace App\Http\Controllers;

use App\Models\RewardItem;
use App\Models\RewardRedemption;
use App\Models\ConsumerNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminRewardItemController extends Controller
{
    public function index(): View
    {
        return view('admin.reward-items', [
            'items' => RewardItem::withCount('redemptions')->orderBy('points_cost')->get(),
            'recentRedemptions' => RewardRedemption::with(['user', 'item'])->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        RewardItem::create($this->validated($request));
        return back()->with('success', 'Récompense ajoutée.');
    }

    public function update(Request $request, RewardItem $rewardItem): RedirectResponse
    {
        $rewardItem->update($this->validated($request));
        return back()->with('success', 'Récompense mise à jour.');
    }

    public function destroy(RewardItem $rewardItem): RedirectResponse
    {
        if ($rewardItem->redemptions()->exists()) {
            $rewardItem->update(['is_active' => false]);
            return back()->with('success', 'Récompense désactivée afin de conserver l’historique.');
        }
        $rewardItem->delete();
        return back()->with('success', 'Récompense supprimée.');
    }

    public function consume(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:30']);

        DB::transaction(function () use ($data) {
            $redemption = RewardRedemption::where('code', strtoupper(trim($data['code'])))
                ->lockForUpdate()->first();
            if (! $redemption || $redemption->status !== 'issued' || ($redemption->expires_at && $redemption->expires_at->isPast())) {
                throw ValidationException::withMessages(['code' => 'Code invalide, expiré ou déjà utilisé.']);
            }

            $redemption->update(['status' => 'used', 'used_at' => now()]);
            ConsumerNotification::create([
                'user_id' => $redemption->user_id,
                'type' => 'reward_used',
                'title' => 'Récompense utilisée',
                'message' => $redemption->item->name.' a bien été validée.',
                'url' => route('consumer.rewards.index'),
            ]);
        });

        return back()->with('success', 'Code validé et marqué comme utilisé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150', 'description' => 'nullable|string|max:1000',
            'points_cost' => 'required|integer|min:1|max:100000', 'partner_name' => 'nullable|string|max:150',
            'stock' => 'nullable|integer|min:0', 'available_until' => 'nullable|date', 'is_active' => 'boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
