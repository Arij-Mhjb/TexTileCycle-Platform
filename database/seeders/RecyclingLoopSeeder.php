<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\RecyclingLoop;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\RecyclingEstimator;
use App\Services\RecyclingLoopService;
use Illuminate\Database\Seeder;

/**
 * Boucles de recyclage de démonstration : une à chaque étape, de la demande à la boucle fermée.
 * Idempotent. Lancer : php artisan db:seed --class=RecyclingLoopSeeder
 */
class RecyclingLoopSeeder extends Seeder
{
    public function run(RecyclingLoopService $service, RecyclingEstimator $estimator): void
    {
        if (RecyclingLoop::where('reference', 'like', 'RCY-DEMO-%')->exists()) {
            return;
        }

        $brandUser = User::where('email', 'brand@textilecycle.test')->first();
        $brand = $brandUser?->brand;
        $suppliers = $brand ? Supplier::where('brand_id', $brand->id)->whereNotNull('user_id')->orderBy('id')->get() : collect();
        if (! $brand || $suppliers->isEmpty()) {
            return;
        }
        $material = fn (string $name) => Material::where('name', $name)->value('id');

        $stories = [
            ['requested', 0, [
                ['defective', 'Jeans — couture d’entrejambe ratée', null, $material('Coton conventionnel'), 25, 17.5, 'Couture d’entrejambe décalée sur toute la série.'],
                ['offcuts', 'Chutes de coupe denim', null, $material('Coton conventionnel'), null, 12, null],
            ], 'Nous aimerions récupérer un fil recyclé pour la prochaine collection.', []],
            ['proposed', 0, [['surplus', 'Rouleaux de polyester non utilisés', null, $material('Polyester vierge'), null, 60, null]], null,
                ['output' => 'Polyester recyclé', 'expected' => 48.6, 'mode' => 'buyback']],
            ['in_transit', 1, [['surplus', 'Fil de laine en fin de série', null, $material('Laine mérinos'), null, 40, null]], 'Fin de série hiver.',
                ['output' => 'Laine recyclée', 'expected' => 32, 'mode' => 'recover']],
            ['received', 0, [['offcuts', 'Chutes de coupe coton bio', null, $material('Coton biologique'), null, 30, null]], null,
                ['output' => 'Coton recyclé', 'expected' => 24, 'mode' => 'recover', 'received' => 29.4]],
            ['delivered', 0, [['defective', 'T-shirts — impression ratée', null, $material('Coton biologique'), 120, 24, 'Impression décalée sur 120 pièces.']], null,
                ['output' => 'Coton recyclé', 'expected' => 17, 'mode' => 'recover', 'received' => 23.6, 'out' => 17.4]],
            ['closed', 1, [['surplus', 'Surplus de laine mérinos', null, $material('Laine mérinos'), null, 55, null]], null,
                ['output' => 'Laine recyclée', 'expected' => 44, 'mode' => 'recover', 'received' => 54.2, 'out' => 43.1]],
            ['closed', 0, [['surplus', 'Rouleaux de polyester de l’an dernier', null, $material('Polyester vierge'), null, 80, null]], 'Nous n’en avons plus besoin : rachetez-les si possible.',
                ['output' => 'Polyester recyclé', 'expected' => 70, 'mode' => 'buyback', 'received' => 79.1, 'out' => 70.4]],
            ['declined', 0, [['defective', 'Vestes — doublure en polyuréthane', null, $material('Polyuréthane'), 15, 18, 'Doublure qui se décolle.']], null,
                ['reason' => 'Nous ne savons pas recycler le polyuréthane : orientez ces vestes vers une filière spécialisée.']],
        ];

        // Il y a combien de jours chaque histoire a commencé : les demandes en cours ont bougé récemment,
        // sauf la première (sans réponse depuis 8 jours) pour montrer une demande qui bloque côté admin.
        $agos = [8, 4, 6, 8, 13, 40, 25, 20];
        $order = ['requested', 'proposed', 'accepted', 'in_transit', 'received', 'transformed', 'delivered', 'closed'];
        foreach ($stories as $index => [$status, $supplierIndex, $items, $message, $offer]) {
            $supplier = $suppliers[$supplierIndex] ?? $suppliers->first();
            $start = now()->subDays($agos[$index] ?? 10)->setTime(9, 30);
            $reached = fn (string $step) => $status !== 'declined' && array_search($status, $order, true) >= array_search($step, $order, true);
            $at = fn (int $days) => $start->copy()->addDays($days);

            $cycle = RecyclingLoop::forceCreate([
                'reference' => 'RCY-DEMO-'.($index + 1),
                'brand_id' => $brand->id,
                'supplier_id' => $supplier->id,
                'requested_by' => $brandUser->id,
                'status' => $status,
                'brand_message' => $message,
                'output_material_id' => isset($offer['output']) ? $material($offer['output']) : null,
                'expected_output_kg' => $offer['expected'] ?? null,
                'mode' => $offer['mode'] ?? 'recover',
                'ready_by' => isset($offer['output']) ? $at(14)->toDateString() : null,
                'decline_reason' => $offer['reason'] ?? null,
                'received_kg' => $offer['received'] ?? null,
                'output_kg' => $offer['out'] ?? null,
                'proposed_at' => $reached('proposed') ? $at(1) : null,
                'accepted_at' => $reached('accepted') ? $at(2) : null,
                'shipped_at' => $reached('in_transit') ? $at(3) : null,
                'received_at' => $reached('received') ? $at(5) : null,
                'transformed_at' => $reached('transformed') ? $at(9) : null,
                'delivered_at' => $reached('delivered') ? $at(10) : null,
                'closed_at' => $reached('closed') ? $at(12) : null,
                'declined_at' => $status === 'declined' ? $at(1) : null,
                'created_at' => $start,
                'updated_at' => $start,
            ]);
            foreach ($items as [$kind, $label, $productId, $materialId, $pieces, $kg, $issue]) {
                $cycle->items()->create(['kind' => $kind, 'label' => $label, 'product_id' => $productId, 'material_id' => $materialId, 'pieces' => $pieces, 'quantity_kg' => $kg, 'issue' => $issue]);
            }
            // Prix calculé automatiquement, comme dans l'application (estimé → proposé → définitif).
            $cycle->load('items');
            if (isset($offer['output'])) {
                $quote = $service->quote($cycle, $offer['output'], (float) ($offer['out'] ?? $offer['expected'])) + ['stage' => isset($offer['out']) ? 'final' : 'proposal'];
            } else {
                $estimate = $estimator->estimate($cycle);
                $quote = $service->quote($cycle, $estimate['material_name'], $estimate['expected_kg']) + ['stage' => 'estimate'];
            }
            $cycle->timestamps = false;
            $lastEvent = collect(['proposed_at', 'accepted_at', 'shipped_at', 'received_at', 'transformed_at', 'delivered_at', 'closed_at', 'declined_at'])
                ->map(fn ($column) => $cycle->{$column})->filter()->max() ?? $start;
            $cycle->forceFill(['pricing' => $quote, 'amount' => $quote['amount'], 'updated_at' => $lastEvent])->save();

            if (isset($offer['out']) && $cycle->mode === 'recover') {
                // updateOrCreate : un ancien lot de démo du même code (seeder précédent) est réutilisé.
                $batch = SupplierBatch::updateOrCreate(['batch_code' => 'LOT-RCY-'.($index + 1)], [
                    'supplier_id' => $supplier->id, 'material_id' => $cycle->output_material_id,
                    'quantity_kg' => $offer['out'], 'origin_country' => $supplier->country ?: 'Tunisie', 'production_date' => $at(9)->toDateString(),
                    'received_at' => $status === 'closed' ? $at(12)->toDateString() : null, 'status' => $status === 'closed' ? 'received' : 'pending',
                ]);
                $cycle->forceFill(['output_batch_id' => $batch->id])->save();
            }
        }
    }
}
