<?php

namespace Database\Seeders;

use App\Models\SupplierBatch;
use App\Models\SupplierReturn;
use Illuminate\Database\Seeder;

/**
 * Retours de démonstration (seconde vie de la matière), un par étape du parcours.
 * Idempotent : ne fait rien si les retours de démo existent déjà.
 * Lancer : php artisan db:seed --class=SupplierReturnSeeder
 */
class SupplierReturnSeeder extends Seeder
{
    public function run(): void
    {
        if (SupplierReturn::where('reference', 'like', 'RTV-DEMO-%')->exists()) {
            return;
        }

        $batches = SupplierBatch::with(['supplier.brand', 'purchaseOrder'])
            ->whereNotNull('received_at')->whereHas('supplier', fn ($query) => $query->whereNotNull('user_id'))
            ->where('quantity_kg', '>=', 40)->orderBy('id')->take(5)->get();
        if ($batches->isEmpty()) {
            return;
        }

        $stories = [
            ['surplus', 'Collection réduite : 2 rouleaux non utilisés.', 'requested', []],
            ['defect', 'Taches d’huile sur un rouleau, visibles à la découpe.', 'accepted', ['destination' => 'recycling', 'pickup_at' => now()->addDays(3)->toDateString(), 'supplier_note' => 'Notre transporteur passe jeudi.']],
            ['wrong_item', 'Coloris écru reçu au lieu de naturel.', 'received', ['destination' => 'resale']],
            ['surplus', 'Fin de série, matière saine.', 'completed', ['destination' => 'resale', 'split' => [0.8, 0.1, 0.1, 0.0]]],
            ['defect', 'Décoloration sur une partie du lot.', 'verified', ['destination' => 'recycling', 'split' => [0.0, 0.85, 0.1, 0.05], 'admin_note' => 'Bon de recyclage conforme.']],
        ];

        foreach ($batches as $index => $batch) {
            [$reason, $description, $status, $extra] = $stories[$index] ?? $stories[0];
            $quantity = round(min(120, (float) $batch->quantity_kg * 0.25), 2);
            $created = now()->subDays(20 - $index * 2);
            $split = $extra['split'] ?? null;
            $parts = $split ? array_map(fn ($share) => round($quantity * $share, 2), $split) : null;
            if ($parts) {
                $parts[2] = round($quantity - $parts[0] - $parts[1] - $parts[3], 2); // le reste : total exact
            }

            SupplierReturn::forceCreate([
                'reference' => 'RTV-DEMO-'.($index + 1),
                'brand_id' => $batch->supplier->brand_id,
                'supplier_id' => $batch->supplier_id,
                'supplier_batch_id' => $batch->id,
                'purchase_order_id' => $batch->purchase_order_id,
                'material_id' => $batch->material_id,
                'requested_by' => $batch->supplier->brand?->user_id,
                'quantity_kg' => $quantity,
                'reason' => $reason,
                'description' => $description,
                'status' => $status,
                'destination' => $extra['destination'] ?? null,
                'pickup_at' => $extra['pickup_at'] ?? null,
                'supplier_note' => $extra['supplier_note'] ?? null,
                'resold_kg' => $parts[0] ?? null,
                'recycled_kg' => $parts[1] ?? null,
                'upcycled_kg' => $parts[2] ?? null,
                'waste_kg' => $parts[3] ?? null,
                'admin_note' => $extra['admin_note'] ?? null,
                'accepted_at' => $status !== 'requested' ? $created->copy()->addDay() : null,
                'shipped_at' => in_array($status, ['received', 'completed', 'verified'], true) ? $created->copy()->addDays(2) : null,
                'received_at' => in_array($status, ['received', 'completed', 'verified'], true) ? $created->copy()->addDays(4) : null,
                'completed_at' => in_array($status, ['completed', 'verified'], true) ? $created->copy()->addDays(7) : null,
                'verified_at' => $status === 'verified' ? $created->copy()->addDays(8) : null,
                'created_at' => $created,
                'updated_at' => $created,
            ]);
        }
    }
}
