<?php

namespace Database\Seeders;

use App\Models\ConsumerNotification;
use App\Models\ProductReturn;
use App\Models\ReturnStatusHistory;
use App\Models\RewardTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConsumerJourneySeeder extends Seeder
{
    public function run(): void
    {
        ProductReturn::query()
            ->where('reference', 'like', 'RET-2026-%')
            ->with(['collectionPoint.recoveryProgram', 'treatmentResult', 'user'])
            ->get()
            ->each(function (ProductReturn $return) {
                $status = match ($return->status) {
                    'processed' => 'completed',
                    'sorting' => 'inspected',
                    default => $return->status,
                };

                $program = $return->collectionPoint?->recoveryProgram;
                $isCompleted = $status === 'completed';
                $baseDate = $return->returned_at ?? $return->created_at ?? now();

                $return->update([
                    'status' => $status,
                    'action' => $program?->type ?? 'recycling',
                    'condition' => $isCompleted ? 'wearable' : 'unknown',
                    'qr_token' => $return->qr_token ?: (string) Str::uuid(),
                    'confirmed_weight_kg' => $isCompleted ? $return->weight_kg : $return->confirmed_weight_kg,
                    'points_awarded' => $isCompleted ? (int) ($program?->reward_points ?? 0) : 0,
                    'deposited_at' => in_array($status, ['received', 'inspected', 'routed', 'completed'], true)
                        ? ($return->deposited_at ?? $baseDate)
                        : null,
                    'completed_at' => $isCompleted
                        ? ($return->completed_at ?? $return->treatmentResult?->processed_at ?? $baseDate)
                        : null,
                ]);

                $rewardStatus = $isCompleted ? 'validated' : 'pending';
                RewardTransaction::updateOrCreate(
                    ['product_return_id' => $return->id],
                    [
                        'user_id' => $return->user_id,
                        'type' => 'return_reward',
                        'points' => (int) ($program?->reward_points ?? 0),
                        'status' => $rewardStatus,
                        'description' => ($isCompleted ? 'Récompense validée pour ' : 'Récompense en attente de validation pour ').$return->reference,
                        'validated_at' => $isCompleted ? ($return->completed_at ?? now()) : null,
                    ]
                );

                $this->seedHistory($return->fresh(), $status, $baseDate);

                if ($return->user?->isConsumer()) {
                    ConsumerNotification::updateOrCreate(
                        [
                            'user_id' => $return->user_id,
                            'type' => 'demo_return_'.$status,
                            'url' => route('product-returns.show', $return),
                        ],
                        [
                            'title' => $isCompleted ? 'Retour valorisé' : 'Retour en cours',
                            'message' => $isCompleted
                                ? 'Le retour '.$return->reference.' est terminé et les points sont validés.'
                                : 'Le retour '.$return->reference.' est actuellement au statut « '.$return->statusLabel().' ».',
                        ]
                    );
                }
            });
    }

    private function seedHistory(ProductReturn $return, string $currentStatus, $baseDate): void
    {
        $sequence = ['declared', 'dropoff_expected', 'received', 'inspected', 'routed', 'completed'];
        $lastIndex = array_search($currentStatus, $sequence, true);
        $statuses = $lastIndex === false ? [$currentStatus] : array_slice($sequence, 0, $lastIndex + 1);

        foreach ($statuses as $index => $status) {
            $history = ReturnStatusHistory::firstOrCreate(
                ['product_return_id' => $return->id, 'status' => $status],
                [
                    'user_id' => $return->user_id,
                    'note' => match ($status) {
                        'declared' => 'Retour déclaré.',
                        'dropoff_expected' => 'Dépôt attendu au point partenaire.',
                        'received' => 'Textile reçu et pesé.',
                        'inspected' => 'Contrôle qualité terminé.',
                        'routed' => 'Textile orienté vers la filière adaptée.',
                        'completed' => 'Valorisation terminée et récompense validée.',
                        default => 'Statut enregistré.',
                    },
                ]
            );

            $date = $baseDate->copy()->addHours($index * 8);
            $history->timestamps = false;
            $history->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
        }
    }
}
