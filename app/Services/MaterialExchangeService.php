<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\ConsumerNotification;
use App\Models\MaterialMatch;
use App\Models\ProductReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaterialExchangeService
{
    public function adminReview(ProductReturn $return, User $admin, string $decision, array $data = []): ProductReturn
    {
        if (! in_array($return->status, ['received', 'inspected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Le produit doit être reçu avant le contrôle administratif.']);
        }

        return DB::transaction(function () use ($return, $admin, $decision, $data) {
            $return->update([
                'condition' => $data['condition'] ?? $return->condition,
                'confirmed_weight_kg' => $data['confirmed_weight_kg'] ?? $return->confirmed_weight_kg,
                'admin_reviewed_by' => $admin->id,
                'admin_review_note' => $data['admin_review_note'] ?? null,
                'admin_review_status' => $decision,
                'admin_verified_at' => $decision === 'verified' ? now() : null,
                'status' => match ($decision) {
                    'verified' => 'marketplace',
                    'rejected' => 'rejected',
                    default => 'received',
                },
            ]);

            $return->statusHistory()->create([
                'user_id' => $admin->id,
                'status' => $return->status,
                'note' => $data['admin_review_note'] ?? 'Contrôle administratif mis à jour.',
            ]);

            if ($decision === 'rejected') {
                $return->rewardTransaction?->update(['status' => 'cancelled']);
            }

            ConsumerNotification::create([
                'user_id' => $return->user_id,
                'type' => 'admin_review_'.$decision,
                'title' => $decision === 'verified' ? 'Textile vérifié' : 'Contrôle du textile',
                'message' => match ($decision) {
                    'verified' => 'Votre textile est vérifié et proposé aux marques compatibles.',
                    'rejected' => 'Votre textile a été refusé. Consultez le motif du contrôle.',
                    default => 'Des informations supplémentaires sont nécessaires.',
                },
                'url' => route('product-returns.show', $return),
            ]);

            if ($decision === 'verified') {
                app(MaterialMatchingService::class)->match($return);
            }

            return $return->fresh();
        });
    }

    public function accept(MaterialMatch $match): MaterialMatch
    {
        if ($match->status !== 'suggested') {
            throw ValidationException::withMessages(['match' => 'Cette proposition n’est plus disponible.']);
        }

        $alreadyReserved = $match->productReturn->materialMatches()
            ->whereKeyNot($match->id)
            ->whereIn('status', ['accepted', 'in_transit', 'brand_received', 'verified'])
            ->exists();
        if ($alreadyReserved) {
            throw ValidationException::withMessages(['match' => 'Ce textile est déjà réservé par une autre marque.']);
        }

        return DB::transaction(function () use ($match) {
            $match->loadMissing(['request.brand', 'productReturn.rewardTransaction']);
            $match->update([
                'status' => 'accepted',
                'transfer_token' => (string) Str::uuid(),
                'accepted_at' => now(),
                'due_at' => now()->addDays(7),
            ]);

            $return = $match->productReturn;
            $return->materialMatches()->whereKeyNot($match->id)->where('status', 'suggested')->update(['status' => 'unavailable']);
            $return->update(['status' => 'reserved_by_brand']);
            $return->statusHistory()->create([
                'user_id' => $match->request->brand->user_id,
                'status' => 'reserved_by_brand',
                'note' => 'Matière réservée par '.$match->request->brand->name.'.',
            ]);

            if ($return->rewardTransaction?->status === 'pending') {
                $return->rewardTransaction->increment('points', $match->request->reward_points);
            }

            ConsumerNotification::create([
                'user_id' => $return->user_id,
                'type' => 'brand_accepted',
                'title' => 'Une marque accepte votre textile',
                'message' => $match->request->brand->name.' a réservé votre textile. Consultez la destination de transfert.',
                'url' => route('product-returns.show', $return),
            ]);

            return $match->fresh();
        });
    }

    public function transfer(MaterialMatch $match, User $consumer): MaterialMatch
    {
        abort_unless($match->productReturn->user_id === $consumer->id, 403);

        return DB::transaction(function () use ($match, $consumer) {
            if ($match->status !== 'accepted') {
                throw ValidationException::withMessages(['transfer' => 'Ce transfert ne peut pas être confirmé.']);
            }

            $match->update(['status' => 'in_transit', 'transferred_at' => now()]);
            $match->productReturn->update(['status' => 'in_transit']);
            $match->productReturn->statusHistory()->create([
                'user_id' => $consumer->id,
                'status' => 'in_transit',
                'note' => 'Dépôt ou transfert confirmé par le consommateur.',
            ]);

            BrandNotification::create([
                'user_id' => $match->request->brand->user_id,
                'type' => 'material_in_transit',
                'title' => 'Textile en transfert',
                'message' => $match->productReturn->reference.' a été déposé pour votre marque.',
                'url' => route('brand.material-matches.index'),
            ]);

            return $match->fresh();
        });
    }

    public function receive(MaterialMatch $match): MaterialMatch
    {
        if (! in_array($match->status, ['accepted', 'in_transit'], true)) {
            throw ValidationException::withMessages(['match' => 'Cette matière ne peut pas être réceptionnée.']);
        }

        return DB::transaction(function () use ($match) {
            $match->update(['status' => 'brand_received', 'brand_received_at' => now()]);
            $match->productReturn->update(['status' => 'brand_review']);
            $match->productReturn->statusHistory()->create([
                'user_id' => $match->request->brand->user_id,
                'status' => 'brand_received',
                'note' => 'Réception confirmée par la marque.',
            ]);

            ConsumerNotification::create([
                'user_id' => $match->productReturn->user_id,
                'type' => 'brand_received',
                'title' => 'Dépôt reçu',
                'message' => 'La marque a confirmé la réception. Le contrôle final est en cours.',
                'url' => route('product-returns.show', $match->productReturn),
            ]);

            return $match->fresh();
        });
    }

    public function verify(MaterialMatch $match, ?string $note = null): MaterialMatch
    {
        if ($match->status !== 'brand_received') {
            throw ValidationException::withMessages(['match' => 'La réception doit être confirmée avant la validation finale.']);
        }

        return DB::transaction(function () use ($match, $note) {
            $match->loadMissing(['request', 'productReturn.rewardTransaction']);
            $match->update([
                'status' => 'verified',
                'brand_verified_at' => now(),
                'brand_review_note' => $note,
            ]);

            $return = $match->productReturn;
            $return->update([
                'status' => 'completed',
                'completed_at' => now(),
                'points_awarded' => $return->rewardTransaction?->points ?? 0,
            ]);
            $return->rewardTransaction?->update(['status' => 'validated', 'validated_at' => now()]);
            $return->statusHistory()->create([
                'user_id' => $match->request->brand->user_id,
                'status' => 'completed',
                'note' => $note ?: 'Matière vérifiée par la marque et récompense validée.',
            ]);

            $weight = (float) ($return->confirmed_weight_kg ?? $return->weight_kg ?? 0);
            $remaining = max(0, (float) $match->request->remaining_kg - $weight);
            $match->request->update([
                'remaining_kg' => $remaining,
                'status' => $remaining <= 0 ? 'closed' : $match->request->status,
            ]);

            ConsumerNotification::create([
                'user_id' => $return->user_id,
                'type' => 'reward_validated',
                'title' => 'Points validés',
                'message' => 'La marque a vérifié votre textile. Vos points sont disponibles dans le wallet.',
                'url' => route('consumer.wallet'),
            ]);

            return $match->fresh();
        });
    }

    public function reject(MaterialMatch $match, string $note): MaterialMatch
    {
        return DB::transaction(function () use ($match, $note) {
            $match->loadMissing(['request', 'productReturn.rewardTransaction']);
            $match->update(['status' => 'rejected', 'brand_review_note' => $note]);
            $return = $match->productReturn;
            $return->update(['status' => 'marketplace']);

            if ($return->rewardTransaction?->status === 'pending') {
                $return->rewardTransaction->update([
                    'points' => max(0, $return->rewardTransaction->points - $match->request->reward_points),
                ]);
            }

            $return->statusHistory()->create([
                'user_id' => $match->request->brand->user_id,
                'status' => 'marketplace',
                'note' => 'Proposition refusée par la marque : '.$note,
            ]);

            ConsumerNotification::create([
                'user_id' => $return->user_id,
                'type' => 'brand_match_rejected',
                'title' => 'Nouvelle recherche de débouché',
                'message' => 'Cette marque n’a pas retenu le textile. Il reste disponible pour d’autres demandes.',
                'url' => route('product-returns.show', $return),
            ]);

            return $match->fresh();
        });
    }
}
