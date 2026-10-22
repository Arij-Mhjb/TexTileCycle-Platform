<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\ConsumerNotification;
use App\Models\LabelRoadmap;
use App\Models\MaterialMatch;
use App\Models\MaterialRequest;
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
        return DB::transaction(function () use ($match) {
            ProductReturn::whereKey($match->product_return_id)->lockForUpdate()->firstOrFail();
            $match = MaterialMatch::whereKey($match->id)->lockForUpdate()->firstOrFail();
            $request = MaterialRequest::whereKey($match->material_request_id)->lockForUpdate()->firstOrFail();
            if ($match->status !== 'suggested' || $match->productReturn->status !== 'marketplace'
                || $match->productReturn->admin_review_status !== 'verified'
                || $request->status !== 'open' || (float) $request->remaining_kg <= 0
                || ($request->expires_at && $request->expires_at->isPast())) {
                throw ValidationException::withMessages(['match' => 'Cette proposition ou cette demande n’est plus disponible.']);
            }
            $alreadyReserved = $match->productReturn->materialMatches()->where('id', '!=', $match->id)
                ->whereIn('status', ['accepted', 'in_transit', 'brand_received', 'verified'])->exists();
            if ($alreadyReserved) {
                throw ValidationException::withMessages(['match' => 'Ce textile est déjà réservé par une autre marque.']);
            }
            $reservedKg = (float) MaterialMatch::where('material_request_id', $request->id)
                ->whereIn('material_matches.status', ['accepted', 'in_transit', 'brand_received'])
                ->join('product_returns', 'product_returns.id', '=', 'material_matches.product_return_id')
                ->sum('product_returns.confirmed_weight_kg');
            $weight = (float) $match->productReturn->confirmed_weight_kg;
            if ($weight <= 0 || $weight > (float) $request->remaining_kg - $reservedKg + 0.001) {
                throw ValidationException::withMessages(['match' => 'La pesée confirmée dépasse la quantité encore disponible pour cette demande.']);
            }
            $match->loadMissing(['request.brand', 'productReturn.rewardTransaction']);
            $match->update([
                'status' => 'accepted',
                'transfer_token' => (string) Str::uuid(),
                'accepted_at' => now(),
                'due_at' => now()->addDays(7),
            ]);

            $return = $match->productReturn;
            $return->materialMatches()->where('id', '!=', $match->id)->where('status', 'suggested')->update(['status' => 'unavailable']);
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
                'message' => $match->request->brand->name.' a réservé votre textile. '.($return->logistics_mode === 'hub_transfer' ? 'Le point de collecte organise sa remise à la marque.' : 'Consultez la destination de transfert.'),
                'url' => route('product-returns.show', $return),
            ]);

            return $match->fresh();
        });
    }

    public function transfer(MaterialMatch $match, User $actor): MaterialMatch
    {
        $return = $match->productReturn;
        $authorized = $actor->isAdmin() || ($actor->isCollector() && $return->logistics_mode === 'hub_transfer'
            && $return->collectionPoint->operator_user_id === $actor->id)
            || ($actor->isConsumer() && $return->logistics_mode === 'consumer_transfer' && $return->user_id === $actor->id);
        abort_unless($authorized, 403);

        return DB::transaction(function () use ($match, $actor) {
            $match = MaterialMatch::whereKey($match->id)->lockForUpdate()->firstOrFail();
            if ($match->status !== 'accepted') {
                throw ValidationException::withMessages(['transfer' => 'Ce transfert ne peut pas être confirmé.']);
            }

            $match->update(['status' => 'in_transit', 'transferred_at' => now()]);
            $match->productReturn->update(['status' => 'in_transit']);
            $match->productReturn->statusHistory()->create([
                'user_id' => $actor->id,
                'status' => 'in_transit',
                'note' => $actor->isConsumer() ? 'Transfert direct confirmé par le consommateur.' : 'Remise à la marque confirmée par le point de collecte.',
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
        return DB::transaction(function () use ($match) {
            $match = MaterialMatch::whereKey($match->id)->lockForUpdate()->firstOrFail();
            if ($match->status !== 'in_transit') {
                throw ValidationException::withMessages(['match' => 'Cette matière ne peut pas être réceptionnée.']);
            }
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
        return DB::transaction(function () use ($match, $note) {
            $roadmap = $match->request->roadmapStep?->roadmap;
            if ($roadmap) {
                LabelRoadmap::whereKey($roadmap->id)->lockForUpdate()->firstOrFail();
            }
            $match = MaterialMatch::whereKey($match->id)->lockForUpdate()->firstOrFail();
            if ($match->status !== 'brand_received') {
                throw ValidationException::withMessages(['match' => 'La réception doit être confirmée avant la validation finale.']);
            }
            MaterialRequest::whereKey($match->material_request_id)->lockForUpdate()->firstOrFail();
            $match->loadMissing(['request', 'productReturn.rewardTransaction']);
            $match->update([
                'status' => 'verified',
                'brand_verified_at' => now(),
                'brand_review_note' => $note,
            ]);

            app(MaterialUsageService::class)->initialize($match);
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
            ProductReturn::whereKey($match->product_return_id)->lockForUpdate()->firstOrFail();
            $match = MaterialMatch::whereKey($match->id)->lockForUpdate()->firstOrFail();
            if (! in_array($match->status, ['accepted', 'in_transit', 'brand_received'], true)) {
                throw ValidationException::withMessages(['match' => 'Seul un échange réservé et non validé peut être refusé.']);
            }
            $match->loadMissing(['request', 'productReturn.rewardTransaction']);
            $match->update(['status' => 'rejected', 'brand_review_note' => $note]);
            $return = $match->productReturn;
            $return->update(['status' => 'marketplace']);
            $return->materialMatches()->where('status', 'unavailable')->where('ai_verdict', 'compatible')->update(['status' => 'suggested']);
            $return->materialMatches()->where('status', 'unavailable')->where('ai_verdict', 'partial')->update(['status' => 'review_needed']);

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
