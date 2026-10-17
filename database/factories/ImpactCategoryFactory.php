<?php

namespace Database\Factories;

use App\Models\ImpactCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImpactCategory>
 */
class ImpactCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('cat_????'),
            'name' => fake()->words(2, true),
            'unit' => fake()->randomElement(['kg', 'L', 'kWh']),
            'weight' => 0.25,
            'description' => fake()->sentence(),
        ];
    }
}
