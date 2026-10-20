<?php

namespace Database\Factories;

use App\Models\RepairPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

class RepairPartnerFactory extends Factory
{
    protected $model = RepairPartner::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => strtolower(str_replace([' ', '&'], '-', $name)),
            'type' => $this->faker->randomElement(['atelier', 'partenaire', 'collectif']),
            'city' => $this->faker->city(),
            'address' => $this->faker->streetAddress(),
            'description' => $this->faker->paragraph(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'is_active' => true,
        ];
    }
}
