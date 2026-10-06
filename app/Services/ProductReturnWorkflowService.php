<?php

namespace App\Services;

use App\Models\CollectionPoint;
use App\Models\ConsumerNotification;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductReturnWorkflowService
{
    private const TRANSITIONS = [
        'declared' => ['dropoff_expected', 'cancelled'],
        'dropoff_expected' => ['received', 'cancelled'],
        'received' => ['inspected', 'rejected'],
        'inspected' => ['routed', 'rejected'],
        'routed' => ['completed', 'rejected'],
        'completed' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    public function create(User $user, array $data): ProductReturn
    {
        return DB::transaction(function () use ($user, $data) {
            $point = $this->validateDestination($data);

            $return = ProductReturn::create([
                ...$data,
                'user_id' => $user->id,
                'reference' => $this->reference(),
                'qr_token' => (string) Str::uuid(),
                'status' => 'declared',
                'points_awarded' => 0,
            ]);

            $return->statusHistory()->create([
                'user_id' => $user->id,
                'status' => 'declared',
                'note' => 'Retour déclaré par le consommateur.',
            ]);

            RewardTransaction::create([
                'user_id' => $user->id,
                'product_return_id' => $return->id,
                'points' => (int) $point->recoveryProgram->reward_points,
                'status' => 'pending',
                'description' => 'Récompense en attente de validation pour '.$return->reference,
            ]);

            $this->notify($return, 'return_declared', 'Retour enregistré', 'Votre retour '.$return->reference.' est prêt à être déposé.');

            return $return;
        });
    }

    public function revise(ProductReturn $return, array $data, User $actor): ProductReturn
    {
        if (! $return->canConsumerEdit() && ! $actor->isAdmin()) {
            throw ValidationException::withMessages(['status' => 'Ce retour ne peut plus être modifié.']);
        }

        return DB::transaction(function () use ($return, $data, $actor) {
            $point = $this->validateDestination($data);
            $return->update($data);

            if ($return->rewardTransaction?->status === 'pending') {
                $return->rewardTransaction->update([
                    'points' => (int) $point->recoveryProgram->reward_points,
                    'description' => 'Récompense en attente de validation pour '.$return->reference,
                ]);
            }

            $return->statusHistory()->create([
                'user_id' => $actor->id,
                'status' => $return->status,
                'note' => 'Informations de dépôt mises à jour.',
            ]);

            return $return->fresh();
        });
    }

    public function transition(ProductReturn $return, string $status, ?User $actor = null, ?string $note = null): ProductReturn
    {
        $current = $this->normalizeLegacyStatus($return->status);
        $target = $this->normalizeLegacyStatus($status);

        if ($target === $current) {
            return $return;
        }

        if (! in_array($target, self::TRANSITIONS[$current] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "Transition impossible de {$current} vers {$target}."]);
        }

        return DB::transaction(function () use ($return, $target, $actor, $note) {
            $updates = ['status' => $target];

            if ($target === 'received') {
                $updates['deposited_at'] = now();
            }

            if ($target === 'completed') {
                $updates['completed_at'] = now();
            }

            if ($target === 'cancelled') {
                $updates['cancelled_at'] = now();
            }

            $return->update($updates);
            $return->statusHistory()->create([
                'user_id' => $actor?->id,
                'status' => $target,
                'note' => $note,
            ]);

            if ($target === 'completed') {
                $transaction = $return->rewardTransaction;
                if ($transaction && $transaction->status !== 'validated') {
                    $transaction->update(['status' => 'validated', 'validated_at' => now()]);
                    $return->update(['points_awarded' => $transaction->points]);
                }
            }

            if (in_array($target, ['cancelled', 'rejected'], true)) {
                $return->rewardTransaction?->update(['status' => 'cancelled']);
            }

            $labels = [
                'dropoff_expected' => 'Votre dépôt est attendu au point sélectionné.',
                'received' => 'Votre textile a été reçu.',
                'inspected' => 'Le contrôle du textile est terminé.',
                'routed' => 'Votre textile a été orienté vers la filière adaptée.',
                'completed' => 'Le traitement est terminé et vos points sont validés.',
                'rejected' => 'Le retour a été refusé. Consultez le motif indiqué.',
                'cancelled' => 'Votre retour a été annulé.',
            ];

            $this->notify($return, 'return_'.$target, 'Mise à jour du retour', $labels[$target] ?? 'Le statut de votre retour a changé.');

            return $return->fresh();
        });
    }

    private function validateDestination(array $data): CollectionPoint
    {
        $productIsAvailable = Product::whereKey($data['product_id'])
            ->where('status', 'published')
            ->where('is_active', true)
            ->exists();

        if (! $productIsAvailable) {
            throw ValidationException::withMessages(['product_id' => 'Ce produit ne peut pas être retourné.']);
        }

        $point = CollectionPoint::with('recoveryProgram')->findOrFail($data['collection_point_id']);

        if (! $point->is_active || ! $point->recoveryProgram?->is_active) {
            throw ValidationException::withMessages(['collection_point_id' => 'Ce point de collecte est indisponible.']);
        }

        if ($point->recoveryProgram->type !== $data['action']) {
            throw ValidationException::withMessages(['collection_point_id' => 'Ce point ne prend pas en charge la solution circulaire choisie.']);
        }

        return $point;
    }

    private function notify(ProductReturn $return, string $type, string $title, string $message): void
    {
        ConsumerNotification::create([
            'user_id' => $return->user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => route('product-returns.show', $return),
        ]);
    }

    private function reference(): string
    {
        do {
            $reference = 'TTC-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        } while (ProductReturn::where('reference', $reference)->exists());

        return $reference;
    }

    private function normalizeLegacyStatus(string $status): string
    {
        return match ($status) {
            'sorting' => 'inspected',
            'processed' => 'completed',
            default => $status,
        };
    }
}
