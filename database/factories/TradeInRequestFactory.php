<?php

namespace Database\Factories;

use App\Models\TradeInRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TradeInRequest>
 */
class TradeInRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'TRD-'.Str::upper(Str::random(8)),
            'user_id' => User::factory()->state(['role' => 'consumer']),
            'product_name' => fake()->randomElement(['Veste en jean', 'Pull en laine', 'Robe en lin', 'Chemise en coton']),
            'condition' => fake()->randomElement(array_keys(TradeInRequest::CONDITIONS)),
            'description' => fake()->sentence(),
            'status' => 'submitted',
        ];
    }
}
