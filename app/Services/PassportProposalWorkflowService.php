<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandNotification;
use App\Models\ConsumerNotification;
use App\Models\CollectionPoint;
use App\Models\Material;
use App\Models\PassportProposal;
use App\Models\Product;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PassportProposalWorkflowService
{
    public function assignCollectionPoints(PassportProposal $proposal, User $admin, array $pointIds): PassportProposal
    {
        abort_unless($admin->isAdmin(), 403);
        $eligiblePointIds = $this->eligiblePointIds($pointIds);
        $proposal->collectionPoints()->sync($eligiblePointIds);
        $this->event(
            $proposal,
            $admin,
            'partner_points_updated',
            $proposal->status,
            $proposal->status,
            $eligiblePointIds->count().' point(s) partenaire(s) autorisé(s).'
        );

        return $proposal->fresh('collectionPoints.recoveryProgram');
    }

    public function consumerSupplement(PassportProposal $proposal, User $consumer, array $data): PassportProposal
    {
        abort_unless($consumer->isConsumer() && $proposal->consumer_id === $consumer->id, 403);
        if ($proposal->status !== 'consumer_information_required') {
            throw ValidationException::withMessages(['proposal' => 'Aucun complément n’est demandé pour cette proposition.']);
        }

        return DB::transaction(function () use ($proposal, $consumer, $data) {
            $from = $proposal->status;
            $merged = array_replace($proposal->proposed_data ?? [], Arr::only($data, $this->fields()));
            $proposal->forceFill([
                'status' => 'admin_pending',
                'proposed_data' => $merged,
                'field_provenance' => array_replace(
                    $proposal->field_provenance ?? [],
                    Arr::only($this->provenance($data), array_keys($data))
                ),
            ])->save();
            $proposal->scan->update(['extracted_data' => array_replace($proposal->scan->extracted_data ?? [], $merged)]);
            $this->event($proposal, $consumer, 'consumer_supplemented', $from, 'admin_pending', 'Informations complétées par le consommateur.');
            $this->notifyConsumer($proposal, 'Complément transmis', 'Les nouvelles informations sont à nouveau examinées par TexTileCycle.');

            return $proposal->fresh(['scan', 'events']);
        });
    }

    public function createFromScan(Scan $scan, User $consumer): PassportProposal
    {
        abort_unless($scan->user_id === $consumer->id && $consumer->isConsumer(), 403);

        return DB::transaction(function () use ($scan, $consumer) {
            $data = $scan->extracted_data ?? [];
            $proposal = PassportProposal::updateOrCreate(
                ['scan_id' => $scan->id],
                [
                    'consumer_id' => $consumer->id,
                    'product_id' => $scan->product_id,
                    'brand_id' => $scan->product?->brand_id ?? $this->matchBrand($data['brand'] ?? null)?->id,
                    'status' => 'admin_pending',
                    'proposed_data' => Arr::only($data, $this->fields()),
                    'field_provenance' => $this->provenance($data),
                    'admin_reviewed_by' => null,
                    'admin_reviewed_at' => null,
                    'admin_note' => null,
                    'brand_reviewed_at' => null,
                    'brand_note' => null,
                    'ready_at' => null,
                ]
            );

            $this->event($proposal, $consumer, 'consumer_confirmed', null, 'admin_pending', 'Informations confirmées par le consommateur.');

            ConsumerNotification::create([
                'user_id' => $consumer->id,
                'type' => 'passport_admin_pending',
                'title' => 'Passeport transmis au contrôle',
                'message' => 'Votre proposition est maintenant examinée par TexTileCycle.',
                'url' => route('scans.show', $scan),
            ]);

            return $proposal;
        });
    }

    public function adminReview(PassportProposal $proposal, User $admin, string $decision, array $input): PassportProposal
    {
        abort_unless($admin->isAdmin(), 403);
        if (! in_array($proposal->status, ['admin_pending', 'brand_rejected', 'consumer_information_required'], true)) {
            throw ValidationException::withMessages(['decision' => 'Cette proposition ne peut plus être contrôlée à cette étape.']);
        }

        return DB::transaction(function () use ($proposal, $admin, $decision, $input) {
            $from = $proposal->status;
            $data = array_replace($proposal->proposed_data ?? [], array_filter(
                Arr::only($input, $this->fields()),
                fn ($value) => $value !== null && $value !== ''
            ));
            $proposal->fill([
                'proposed_data' => $data,
                'brand_id' => $input['brand_id'] ?? $proposal->brand_id ?? $this->matchBrand($data['brand'] ?? null)?->id,
                'product_id' => $input['product_id'] ?? $proposal->product_id,
                'admin_reviewed_by' => $admin->id,
                'admin_reviewed_at' => now(),
                'admin_note' => $input['note'] ?? null,
            ]);

            if ($decision === 'request_information') {
                $proposal->status = 'consumer_information_required';
                $proposal->save();
                $this->notifyConsumer($proposal, 'Informations complémentaires demandées', $input['note'] ?? 'TexTileCycle demande une nouvelle vérification des informations.');
            } elseif ($decision === 'reject') {
                $proposal->status = 'admin_rejected';
                $proposal->save();
                $this->notifyConsumer($proposal, 'Proposition refusée', $input['note'] ?? 'La proposition ne peut pas être validée en l’état.');
            } else {
                $eligiblePointIds = $this->eligiblePointIds($input['collection_point_ids'] ?? []);
                $proposal->collectionPoints()->sync($eligiblePointIds);
                $product = $this->resolveProduct($proposal, $data);
                $proposal->product_id = $product->id;
                $proposal->scan->update(['product_id' => $product->id]);

                if ($proposal->brand_id) {
                    $proposal->status = 'brand_pending';
                    $proposal->save();
                    BrandNotification::create([
                        'user_id' => $proposal->brand->user_id,
                        'type' => 'passport_proposal',
                        'title' => 'Nouvelle proposition de passeport',
                        'message' => 'TexTileCycle vous demande de vérifier un produit attribué à votre marque.',
                        'url' => route('brand.passport-proposals.show', $proposal),
                    ]);
                    $this->notifyConsumer($proposal, 'Contrôle Admin terminé', 'La proposition a été transmise à la marque pour confirmation.');
                } else {
                    $this->publish($product);
                    $proposal->forceFill(['status' => 'ready_for_return', 'ready_at' => now()])->save();
                    $this->notifyConsumer($proposal, 'Passeport prêt', 'Les données ont été contrôlées. Vous pouvez préparer le retour circulaire.');
                }
            }

            $this->event($proposal, $admin, 'admin_'.$decision, $from, $proposal->status, $input['note'] ?? null);

            return $proposal->fresh(['scan', 'brand', 'product']);
        });
    }

    public function brandReview(PassportProposal $proposal, User $user, string $decision, array $input): PassportProposal
    {
        abort_unless($user->isBrand() && $user->brand?->id === $proposal->brand_id, 403);
        if ($proposal->status !== 'brand_pending') {
            throw ValidationException::withMessages(['decision' => 'Cette proposition n’est plus en attente de votre marque.']);
        }

        return DB::transaction(function () use ($proposal, $user, $decision, $input) {
            $from = $proposal->status;
            if ($decision === 'reject') {
                $proposal->forceFill([
                    'status' => 'brand_rejected',
                    'brand_reviewed_at' => now(),
                    'brand_note' => $input['note'] ?? null,
                ])->save();
                $this->notifyConsumer($proposal, 'Réexamen du passeport', 'La marque conteste l’attribution. TexTileCycle va réexaminer la proposition.');
            } else {
                $data = array_replace($proposal->proposed_data ?? [], array_filter(
                    Arr::only($input, $this->fields()),
                    fn ($value) => $value !== null && $value !== ''
                ));
                $proposal->forceFill([
                    'proposed_data' => $data,
                    'status' => 'ready_for_return',
                    'brand_reviewed_at' => now(),
                    'brand_note' => $input['note'] ?? null,
                    'ready_at' => now(),
                ])->save();
                $product = $this->resolveProduct($proposal, $data);
                $this->publish($product);
                $this->notifyConsumer($proposal, 'Passeport confirmé', 'La marque a confirmé ou corrigé les informations. Le retour circulaire est disponible.');
            }

            $this->event($proposal, $user, 'brand_'.$decision, $from, $proposal->status, $input['note'] ?? null);

            return $proposal->fresh(['scan', 'brand', 'product']);
        });
    }

    private function resolveProduct(PassportProposal $proposal, array $data): Product
    {
        $barcode = filled($data['barcode'] ?? null) ? trim((string) $data['barcode']) : null;
        $barcodeProduct = $barcode
            ? Product::withTrashed()->where('barcode', $barcode)->first()
            : null;

        if ($barcodeProduct) {
            if ($barcodeProduct->trashed()) {
                $barcodeProduct->restore();
            }

            $brandId = $barcodeProduct->brand_id ?: $proposal->brand_id;
            $barcodeProduct->forceFill(array_filter([
                'brand_id' => $brandId,
                'brand' => $barcodeProduct->brand ?: ($proposal->brand?->name ?? ($data['brand'] ?? null)),
                'origin' => $barcodeProduct->origin ?: ($data['manufacturing_country'] ?? null),
            ], fn ($value) => $value !== null && $value !== ''))->save();

            $proposal->forceFill([
                'product_id' => $barcodeProduct->id,
                'brand_id' => $brandId,
            ])->save();
            $proposal->scan->update(['product_id' => $barcodeProduct->id]);
            $proposal->unsetRelation('product');
            $proposal->unsetRelation('brand');

            if (! $barcodeProduct->materials()->exists()) {
                $this->syncComposition($barcodeProduct, $data['composition'] ?? []);
            }

            return $barcodeProduct;
        }

        $product = $proposal->product;
        $sku = filled($data['sku'] ?? null) ? (string) $data['sku'] : 'SCAN-'.$proposal->scan->uuid;
        if (! $product) {
            $sku = $this->uniqueSku($sku);
            $product = new Product;
            $product->slug = Str::slug(($data['product_name'] ?? 'textile').'-'.Str::random(6));
            $product->sku = $sku;
        }

        $product->fill([
            'brand_id' => $proposal->brand_id,
            'brand' => $proposal->brand?->name ?? ($data['brand'] ?? 'Marque non identifiée'),
            'name' => $data['product_name'] ?? 'Produit textile proposé',
            'barcode' => $barcode ?? $product->barcode,
            'category' => $data['category'] ?? 'Textile',
            'origin' => $data['manufacturing_country'] ?? $product->origin,
            'description' => 'Passeport proposé depuis le Smart Scan #'.$proposal->scan_id.'.',
            'status' => 'draft',
            'is_active' => false,
        ]);
        $product->save();
        $proposal->forceFill(['product_id' => $product->id])->save();
        $this->syncComposition($product, $data['composition'] ?? []);

        return $product;
    }

    private function syncComposition(Product $product, array $composition): void
    {
        $sync = [];
        foreach ($composition as $item) {
            if (! filled($item['material'] ?? null) || ! is_numeric($item['percentage'] ?? null)) {
                continue;
            }
            $material = Material::firstOrCreate(
                ['name' => trim($item['material'])],
                ['type' => 'textile', 'recyclable' => true, 'animal_origin' => false]
            );
            $sync[$material->id] = [
                'percentage' => (float) $item['percentage'],
                'origin_country' => $product->origin,
                'certified' => false,
                'component' => 'main',
            ];
        }
        if ($sync !== []) {
            $product->materials()->sync($sync);
        }
    }

    private function publish(Product $product): void
    {
        $product->forceFill([
            'status' => 'published',
            'is_active' => true,
            'verified_at' => now(),
            'published_at' => now(),
        ])->save();
    }

    private function matchBrand(?string $name): ?Brand
    {
        return filled($name)
            ? Brand::where('is_active', true)->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])->first()
            : null;
    }

    private function uniqueSku(string $candidate): string
    {
        $candidate = Str::upper(Str::limit(preg_replace('/[^A-Z0-9-]/i', '-', $candidate), 85, ''));
        $sku = $candidate;
        while (Product::withTrashed()->where('sku', $sku)->exists()) {
            $sku = $candidate.'-'.Str::upper(Str::random(5));
        }

        return $sku;
    }

    private function eligiblePointIds(array $ids)
    {
        $pointIds = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();
        $eligiblePointIds = CollectionPoint::whereIn('id', $pointIds)
            ->where('is_active', true)
            ->whereHas('recoveryProgram', fn ($program) => $program->where('is_active', true))
            ->pluck('id');
        if ($pointIds->isEmpty() || $eligiblePointIds->count() !== $pointIds->count()) {
            throw ValidationException::withMessages([
                'collection_point_ids' => 'Sélectionnez au moins un point partenaire actif correspondant au produit.',
            ]);
        }

        return $eligiblePointIds;
    }

    private function provenance(array $data): array
    {
        $sources = $data['field_sources'] ?? [];

        return collect($this->fields())->mapWithKeys(fn ($field) => [
            $field => [
                'source' => $sources[$field] ?? 'consumer_or_local',
                'confidence' => data_get($data, 'field_confidence.'.$field),
                'evidence' => data_get($data, 'field_evidence.'.$field),
            ],
        ])->all();
    }

    private function notifyConsumer(PassportProposal $proposal, string $title, string $message): void
    {
        ConsumerNotification::create([
            'user_id' => $proposal->consumer_id,
            'type' => 'passport_'.$proposal->status,
            'title' => $title,
            'message' => $message,
            'url' => route('scans.show', $proposal->scan_id),
        ]);
    }

    private function event(PassportProposal $proposal, ?User $actor, string $event, ?string $from, ?string $to, ?string $note): void
    {
        $proposal->events()->create([
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'event' => $event,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    private function fields(): array
    {
        return [
            'brand', 'product_name', 'sku', 'barcode', 'category', 'color', 'size',
            'manufacturing_country', 'retailer', 'purchase_date', 'purchase_price', 'currency',
            'composition', 'condition', 'defects',
        ];
    }
}
