<?php

namespace Database\Factories;

use App\Models\RepairCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairCenter>
 */
class RepairCenterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Atelier', 'Retouche', 'ReFab', 'Couture']).' '.fake()->unique()->lastName(),
            'city' => fake()->randomElement(['Tunis', 'Sousse', 'Sfax', 'Nabeul', 'Bizerte', 'Monastir']),
            'address' => fake()->streetAddress(),
            'specialties' => fake()->randomElement(['Couture, retouches', 'Chaussures, cuir', 'Reconditionnement, nettoyage']),
            'contact_email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'capacity_per_week' => fake()->numberBetween(5, 30),
            'is_active' => true,
        ];
    }
}
