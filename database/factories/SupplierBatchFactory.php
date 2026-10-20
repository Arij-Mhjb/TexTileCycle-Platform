<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupplierBatch>
 */
class SupplierBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'material_id' => fn () => Material::query()->inRandomOrder()->value('id')
                ?? Material::create(['name' => 'Coton recyclé', 'type' => 'recycled', 'recyclable' => true])->id,
            'batch_code' => 'LOT-'.Str::upper(Str::random(3)).'-'.fake()->unique()->numberBetween(1000, 9999),
            'quantity_kg' => fake()->randomFloat(2, 50, 800),
            'origin_country' => fn (array $attributes) => Supplier::find($attributes['supplier_id'])->country,
            'production_date' => fake()->dateTimeBetween('-8 months', '-2 months'),
            'received_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'status' => 'received',
        ];
    }

    public function verified(): static
    {
        return $this->state(['status' => 'verified', 'verified_at' => now()]);
    }
}
