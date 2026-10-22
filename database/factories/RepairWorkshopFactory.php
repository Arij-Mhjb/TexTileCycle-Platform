<?php

namespace Database\Factories;

use App\Models\RepairPartner;
use App\Models\RepairWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

class RepairWorkshopFactory extends Factory
{
    protected $model = RepairWorkshop::class;

    public function definition(): array
    {
        $name = $this->faker->company() . ' Atelier';

        return [
            'repair_partner_id' => RepairPartner::query()->inRandomOrder()->first()?->id ?? RepairPartner::factory(),
            'name' => $name,
            'slug' => strtolower(str_replace([' ', '&'], '-', $name)),
            'city' => $this->faker->city(),
            'address' => $this->faker->streetAddress(),
            'specialties' => 'Réparation, retouche, renfort textile',
            'description' => $this->faker->paragraph(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'capacity_per_week' => $this->faker->numberBetween(10, 50),
            'is_active' => true,
        ];
    }
}
