<?php

namespace Database\Factories;

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplaceListing>
 */
class MarketplaceListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => fn () => Product::query()->whereNotNull('brand_id')->inRandomOrder()->value('id'),
            'brand_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->brand_id,
            'title' => fn (array $attributes) => (Product::find($attributes['product_id'])?->name ?? 'Article').' — reconditionné',
            'description' => fake()->sentence(12),
            'condition_grade' => fake()->randomElement(['A', 'B', 'C']),
            'price' => fake()->randomFloat(2, 25, 250),
            'points_price' => fake()->randomElement([null, 300, 450, 600, 800]),
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
