<?php

namespace App\Services;

use App\Models\ConsumerNotification;
use App\Models\MarketplaceListing;
use App\Models\ProductReturn;
use App\Models\RepairOrder;
use App\Models\ResaleOffer;
use App\Models\RewardTransaction;
use App\Models\TradeInRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Seconde vie : retour → diagnostic → réparation / reconditionnement → marketplace,
 * offres de rachat et reprises (trade-in) avec récompenses en points.
 */
class RepairMarketplaceService
{
    public const RESALE_POINTS = 60;

    public const REPAIR_POINTS = 25;

    public function requestRepair(User $consumer, array $data): RepairOrder
    {
        return DB::transaction(function () use ($consumer, $data) {
            // Verrou sur le retour : deux envois simultanés (double clic) ne créent pas deux demandes.
            $return = ! empty($data['product_return_id']) ? ProductReturn::whereKey($data['product_return_id'])->lockForUpdate()->firstOrFail() : null;
            if ($return && $return->user_id !== $consumer->id) {
                abort(403);
            }
            if ($return && RepairOrder::where('product_return_id', $return->id)->where('status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages(['product_return_id' => 'Une demande de réparation existe déjà pour cet article.']);
            }

            return RepairOrder::create([
                'reference' => $this->reference('REP'),
                'user_id' => $consumer->id,
                'product_return_id' => $return?->id,
                'product_id' => $return?->product_id ?? ($data['product_id'] ?? null),
                'issue_description' => $data['issue_description'],
                'status' => 'requested',
            ]);
        });
    }

    public function createFromReturn(ProductReturn $return, string $decision, ?string $note = null): RepairOrder
    {
        return RepairOrder::firstOrCreate(
            ['product_return_id' => $return->id],
            [
                'reference' => $this->reference('REP'),
                'user_id' => $return->user_id,
                'product_id' => $return->product_id,
                'issue_description' => $note ?: 'Retour '.$return->reference.' orienté vers la seconde vie (état : '.($return->condition ?: 'à diagnostiquer').').',
                'decision' => $decision,
                'status' => 'requested',
            ]
        );
    }

    public function diagnose(RepairOrder $order, array $data): RepairOrder
    {
        $order->update([
            'decision' => $data['decision'],
            'repair_center_id' => $data['repair_center_id'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
            'status' => $data['decision'] === 'recycle' ? 'closed' : 'diagnosed',
            'diagnosed_at' => now(),
            'completed_at' => $data['decision'] === 'recycle' ? now() : null,
        ]);

        $this->notify($order->user_id, 'repair_diagnosed', 'Diagnostic de votre article',
            $order->reference.' : '.$order->decisionLabel().'.');

        return $order;
    }

    public function startRepair(RepairOrder $order): RepairOrder
    {
        $this->guard($order->status === 'diagnosed' && $order->repair_center_id, 'Le diagnostic et le centre de réparation sont requis.');
        $order->update(['status' => 'in_repair']);

        return $order;
    }

    public function refurbish(RepairOrder $order, array $data): RepairOrder
    {
        $this->guard(in_array($order->status, ['diagnosed', 'in_repair'], true), 'La commande doit être diagnostiquée.');

        DB::transaction(function () use ($order, $data) {
            $order->refurbishments()->create([...$data, 'completed_at' => now()]);
            $order->update(['status' => 'repaired', 'final_cost' => $data['cost'] ?? $order->estimated_cost]);
        });

        return $order->fresh();
    }

    /** Réparation rendue au consommateur : clôture + points. */
    public function closeRepair(RepairOrder $order): RepairOrder
    {
        $this->guard($order->status === 'repaired' && $order->decision === 'repair', 'Seule une réparation terminée peut être clôturée.');

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'closed', 'completed_at' => now(), 'reward_points' => self::REPAIR_POINTS]);
            $this->reward($order->user_id, 'repair', self::REPAIR_POINTS, 'Article réparé et remis en circulation · '.$order->reference);
        });
        $this->notify($order->user_id, 'repair_closed', 'Votre article est réparé', $order->reference.' : +'.self::REPAIR_POINTS.' points.');

        return $order;
    }

    /** Reconditionné : publication sur le marketplace + points au consommateur. */
    public function publish(RepairOrder $order, array $data): MarketplaceListing
    {
        $this->guard($order->status === 'repaired' && $order->decision === 'resale', 'L’article doit être reconditionné pour être revendu.');

        return DB::transaction(function () use ($order, $data) {
            $grade = $order->refurbishments()->latest()->value('grade') ?? 'B';
            $listing = MarketplaceListing::create([
                'product_id' => $order->product_id,
                'brand_id' => $order->product?->brand_id,
                'repair_order_id' => $order->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'condition_grade' => $grade,
                'price' => $data['price'],
                'status' => 'published',
                'published_at' => now(),
            ]);
            $order->update(['status' => 'listed', 'completed_at' => now(), 'reward_points' => self::RESALE_POINTS]);
            $this->reward($order->user_id, 'second_life', self::RESALE_POINTS, 'Article reconditionné et remis en vente · '.$order->reference);
            $this->notify($order->user_id, 'second_life', 'Votre article a une seconde vie',
                $listing->title.' est en vente sur le marketplace : +'.self::RESALE_POINTS.' points.', route('consumer.marketplace.show', $listing));

            return $listing;
        });
    }

    public function makeOffer(MarketplaceListing $listing, User $buyer, float $price, ?string $message = null): ResaleOffer
    {
        $this->guard($listing->status === 'published', 'Cette annonce n’est plus disponible.');
        $this->guard(! $listing->isFromConsumer($buyer), 'Cet article vient de votre propre retour : vous ne pouvez pas faire d’offre dessus.');
        $this->guard(! $listing->offers()->where('user_id', $buyer->id)->where('status', 'pending')->exists(), 'Vous avez déjà une offre en attente sur cette annonce.');

        return $listing->offers()->create(['user_id' => $buyer->id, 'offered_price' => $price, 'message' => $message, 'status' => 'pending']);
    }

    public function respondToOffer(ResaleOffer $offer, bool $accept): ResaleOffer
    {
        $this->guard($offer->status === 'pending', 'Cette offre a déjà reçu une réponse.');

        DB::transaction(function () use ($offer, $accept) {
            $offer->update(['status' => $accept ? 'accepted' : 'declined', 'responded_at' => now()]);
            if ($accept) {
                $offer->listing->update(['status' => 'reserved']);
                $offer->listing->offers()->where('id', '!=', $offer->id)->where('status', 'pending')
                    ->update(['status' => 'declined', 'responded_at' => now()]);
            }
        });

        $this->notify($offer->user_id, 'resale_offer_'.($accept ? 'accepted' : 'declined'),
            $accept ? 'Offre acceptée' : 'Offre refusée',
            $offer->listing->title.' · '.number_format((float) $offer->offered_price, 2, ',', ' ').' '.$offer->listing->currency,
            route('consumer.marketplace.show', $offer->listing));

        return $offer;
    }

    public function markSold(MarketplaceListing $listing): MarketplaceListing
    {
        $this->guard($listing->status === 'reserved', 'Seule une annonce réservée peut être vendue.');
        $listing->update(['status' => 'sold', 'sold_at' => now()]);
        $listing->repairOrder?->update(['status' => 'closed']);

        return $listing;
    }

    public function submitTradeIn(User $consumer, array $data): TradeInRequest
    {
        return TradeInRequest::create([
            ...$data,
            'reference' => $this->reference('TRD'),
            'user_id' => $consumer->id,
            'status' => 'submitted',
        ]);
    }

    public function evaluateTradeIn(TradeInRequest $tradeIn, User $admin, int $points, ?string $note = null): TradeInRequest
    {
        $this->guard(in_array($tradeIn->status, ['submitted', 'evaluated'], true), 'Cette reprise a déjà été traitée.');
        $tradeIn->update([
            'proposed_points' => $points,
            'evaluation_note' => $note,
            'evaluated_by' => $admin->id,
            'evaluated_at' => now(),
            'status' => 'evaluated',
        ]);
        $this->notify($tradeIn->user_id, 'trade_in_evaluated', 'Offre de reprise disponible',
            $tradeIn->itemName().' : '.$points.' points proposés.', route('consumer.trade-ins.index'));

        return $tradeIn;
    }

    public function answerTradeIn(TradeInRequest $tradeIn, bool $accept): TradeInRequest
    {
        $this->guard($tradeIn->status === 'evaluated', 'Aucune offre à accepter.');
        $tradeIn->update(['status' => $accept ? 'accepted' : 'declined']);

        if ($accept) {
            $this->reward($tradeIn->user_id, 'trade_in', (int) $tradeIn->proposed_points,
                'Reprise '.$tradeIn->reference.' · validée à la réception', 'pending');
        }

        return $tradeIn;
    }

    public function completeTradeIn(TradeInRequest $tradeIn): TradeInRequest
    {
        $this->guard($tradeIn->status === 'accepted', 'La reprise doit être acceptée par le consommateur.');

        DB::transaction(function () use ($tradeIn) {
            $tradeIn->update(['status' => 'completed', 'completed_at' => now()]);
            RewardTransaction::where('user_id', $tradeIn->user_id)->where('type', 'trade_in')->where('status', 'pending')
                ->where('description', 'like', 'Reprise '.$tradeIn->reference.'%')
                ->update(['status' => 'validated', 'validated_at' => now()]);
        });
        $this->notify($tradeIn->user_id, 'trade_in_completed', 'Reprise validée',
            $tradeIn->itemName().' reçu : +'.$tradeIn->proposed_points.' points crédités.', route('consumer.wallet'));

        return $tradeIn;
    }

    private function reward(int $userId, string $type, int $points, string $description, string $status = 'validated'): void
    {
        if ($points <= 0) {
            return;
        }

        RewardTransaction::create([
            'user_id' => $userId,
            'type' => $type,
            'points' => $points,
            'status' => $status,
            'description' => $description,
            'validated_at' => $status === 'validated' ? now() : null,
        ]);
    }

    private function notify(int $userId, string $type, string $title, string $message, ?string $url = null): void
    {
        ConsumerNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url ?? route('consumer.repairs.index'),
        ]);
    }

    private function guard(mixed $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function reference(string $prefix): string
    {
        return $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }
}
