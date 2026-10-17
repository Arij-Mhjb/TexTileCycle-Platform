<?php

namespace Database\Factories;

use App\Models\ImpactCategory;
use App\Models\ImpactMetric;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImpactMetric>
 */
class ImpactMetricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'material_id' => fn () => Material::query()->inRandomOrder()->value('id'),
            'impact_category_id' => ImpactCategory::factory(),
            'value_per_kg' => fake()->randomFloat(4, 0.05, 25),
            'source' => 'Factory',
        ];
    }
}
