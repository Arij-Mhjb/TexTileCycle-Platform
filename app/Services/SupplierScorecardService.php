<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Performance fournisseur, avec les indicateurs utilisés en achats :
 *  - OTIF (On Time In Full) : commandes livrées à la date promise ET en totalité ;
 *  - taux de service (fill rate) : kg reçus / kg commandés ;
 *  - retard moyen, réactivité (délai de réponse à une commande), conformité des preuves.
 * Note globale /100 = 40 % OTIF + 20 % taux de service + 20 % conformité + 20 % réactivité.
 */
class SupplierScorecardService
{
    /** Délai de réponse jugé excellent / inacceptable (heures). */
    private const FAST_REPLY_HOURS = 24;

    private const SLOW_REPLY_HOURS = 120;

    /** @return Collection<int, array<string, mixed>> une ligne par fournisseur, triée par note décroissante */
    public function ranking(Collection $suppliers): Collection
    {
        $suppliers->loadMissing(['purchaseOrders.lines.batches']);

        return $suppliers->map(fn (Supplier $supplier) => $this->card($supplier))
            ->sortByDesc(fn (array $card) => [$card['evaluated'] > 0 ? 1 : 0, $card['score'] ?? -1])
            ->values();
    }

    /** @return array<string, mixed> */
    public function card(Supplier $supplier): array
    {
        $orders = $supplier->purchaseOrders->reject(fn (PurchaseOrder $order) => in_array($order->status, ['declined', 'cancelled'], true));
        $evaluated = $orders->filter(fn (PurchaseOrder $order) => $this->isEvaluable($order));

        $onTime = 0;
        $inFull = 0;
        $otif = 0;
        $delays = [];
        $due = 0.0;
        $received = 0.0;
        foreach ($evaluated as $order) {
            $delay = $this->delayDays($order);
            $isOnTime = $delay <= 0;
            $isInFull = ! $order->closed_short_at && $order->receivedKg() >= $order->dueKg() * (1 - PurchaseOrder::UNDER_DELIVERY_TOLERANCE);
            $onTime += $isOnTime ? 1 : 0;
            $inFull += $isInFull ? 1 : 0;
            $otif += $isOnTime && $isInFull ? 1 : 0;
            if (! $isOnTime) {
                $delays[] = $delay;
            }
            $due += $order->dueKg();
            $received += $order->receivedKg();
        }

        $replyHours = $supplier->purchaseOrders
            ->map(function (PurchaseOrder $order) {
                $answeredAt = $order->accepted_at ?? $order->countered_at ?? $order->declined_at;
                $sentAt = $order->sent_at ?? $order->created_at;

                return $answeredAt && $sentAt ? max(0, $sentAt->diffInMinutes($answeredAt) / 60) : null;
            })
            ->filter(fn ($hours) => $hours !== null);

        $count = $evaluated->count();
        $otifRate = $count ? round($otif / $count * 100) : null;
        $fillRate = $due > 0 ? (int) min(100, round($received / $due * 100)) : null;
        $avgReply = $replyHours->isNotEmpty() ? round($replyHours->avg(), 1) : null;
        $responsiveness = $avgReply === null ? null
            : (int) round(max(0, min(100, 100 * (self::SLOW_REPLY_HOURS - $avgReply) / (self::SLOW_REPLY_HOURS - self::FAST_REPLY_HOURS))));
        $compliance = $supplier->compliance_score;

        $parts = array_filter([
            [$otifRate, 0.4], [$fillRate, 0.2], [$compliance, 0.2], [$responsiveness, 0.2],
        ], fn ($part) => $part[0] !== null);
        $weight = array_sum(array_column($parts, 1));
        // Sans livraison évaluée, pas de note : la conformité seule ne dit rien de la fiabilité.
        $score = $count > 0 && $weight > 0 ? (int) round(array_sum(array_map(fn ($part) => $part[0] * $part[1], $parts)) / $weight) : null;

        return [
            'supplier' => $supplier,
            'orders' => $orders->count(),
            'evaluated' => $count,
            'open_late' => $orders->filter(fn (PurchaseOrder $order) => $order->isOpen() && $order->isLate())->count(),
            'on_time' => $count ? (int) round($onTime / $count * 100) : null,
            'in_full' => $count ? (int) round($inFull / $count * 100) : null,
            'otif' => $otifRate,
            'fill_rate' => $fillRate,
            'avg_delay_days' => $delays ? round(array_sum($delays) / count($delays), 1) : 0,
            'avg_reply_hours' => $avgReply,
            'responsiveness' => $responsiveness,
            'compliance' => $compliance,
            'score' => $score,
            'grade' => $score === null ? null : ($score >= 85 ? 'A' : ($score >= 70 ? 'B' : ($score >= 50 ? 'C' : 'D'))),
        ];
    }

    /** Une commande compte si elle est réceptionnée/soldée, ou si sa date promise est dépassée sans livraison complète (en transit : pas encore jugée). */
    private function isEvaluable(PurchaseOrder $order): bool
    {
        if ($order->status === 'received' || $order->closed_short_at) {
            return true;
        }

        return in_array($order->status, ['accepted', 'partially_shipped'], true)
            && $order->expected_at && $order->expected_at->isPast() && ! $order->expected_at->isToday();
    }

    /** Jours de retard (≤ 0 = à l'heure) : dernière réception, sinon aujourd'hui si rien n'est arrivé. */
    private function delayDays(PurchaseOrder $order): int
    {
        if (! $order->expected_at) {
            return 0;
        }
        $deliveredAt = $order->lines->flatMap->batches->pluck('received_at')->filter()->max()
            ?? $order->received_at
            ?? now();

        return (int) $order->expected_at->startOfDay()->diffInDays(Carbon::parse($deliveredAt)->startOfDay(), false);
    }
}
