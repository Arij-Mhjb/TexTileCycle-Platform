<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        $ordered = fake()->dateTimeBetween('-6 months', '-1 month');

        return [
            'supplier_id' => Supplier::factory(),
            // La commande appartient à la même marque que son fournisseur.
            'brand_id' => fn (array $attributes) => Supplier::find($attributes['supplier_id'])->brand_id,
            'reference' => 'PO-'.Str::upper(Str::random(8)),
            'status' => fake()->randomElement(['ordered', 'received']),
            'ordered_at' => $ordered,
            'expected_at' => (clone $ordered)->modify('+3 weeks'),
            'total_amount' => fake()->randomFloat(2, 2000, 40000),
        ];
    }
}
