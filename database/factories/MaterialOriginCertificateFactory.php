<?php

namespace Database\Factories;

use App\Models\MaterialOriginCertificate;
use App\Models\SupplierBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialOriginCertificate>
 */
class MaterialOriginCertificateFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['GOTS', 'GRS', 'OEKO-TEX', 'origin']);

        return [
            'supplier_batch_id' => SupplierBatch::factory(),
            'type' => $type,
            'certificate_number' => $type.'-'.fake()->unique()->numerify('####-####'),
            'issuer' => fake()->randomElement(['Control Union', 'Ecocert', 'Bureau Veritas', 'SGS']),
            'issued_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'expires_at' => fake()->dateTimeBetween('+6 months', '+2 years'),
            'status' => 'pending',
        ];
    }

    public function verified(): static
    {
        return $this->state(['status' => 'verified', 'reviewed_at' => now()]);
    }
}
