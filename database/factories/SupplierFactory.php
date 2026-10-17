<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        $country = fake()->randomElement(['Tunisie', 'Portugal', 'Italie', 'Turquie', 'Maroc', 'France']);

        return [
            // Brand n'a pas le trait HasFactory (modèle de l'équipe) : on appelle sa factory directement.
            'brand_id' => BrandFactory::new(),
            'name' => fake()->unique()->randomElement(['Tissage', 'Filature', 'Teinturerie', 'Atelier', 'Textiles']).' '.fake()->unique()->lastName(),
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => $country,
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'certifications' => fake()->randomElement(['GOTS', 'GRS', 'OEKO-TEX', 'GRS, OEKO-TEX', null]),
            'status' => 'active',
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }
}
