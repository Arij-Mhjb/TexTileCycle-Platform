<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\MaterialRequest;
use Illuminate\Database\Seeder;

class MaterialExchangeSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::where('is_active', true)->first();

        if (! $brand) {
            return;
        }

        MaterialRequest::updateOrCreate(
            ['brand_id' => $brand->id, 'title' => 'Coton circulaire pour nouvelle collection'],
            [
                'material_name' => 'Coton',
                'minimum_percentage' => 70,
                'categories' => ['Chemise', 'Pantalon', 'Robe'],
                'accepted_conditions' => ['good', 'wearable', 'minor_damage', 'damaged'],
                'quantity_kg' => 100,
                'remaining_kg' => 100,
                'city' => 'Tunis',
                'dropoff_address' => 'EcoFashion Lac 2, Tunis',
                'reward_points' => 75,
                'status' => 'open',
                'expires_at' => now()->addMonths(3),
            ]
        );

        MaterialRequest::updateOrCreate(
            ['brand_id' => $brand->id, 'title' => 'Alternative cuir végétale'],
            [
                'material_name' => 'cactus',
                'minimum_percentage' => 60,
                'categories' => ['Sac', 'Chaussures'],
                'accepted_conditions' => ['good', 'wearable', 'minor_damage'],
                'quantity_kg' => 40,
                'remaining_kg' => 40,
                'city' => 'Tunis',
                'dropoff_address' => 'EcoFashion Lac 2, Tunis',
                'reward_points' => 120,
                'status' => 'open',
                'expires_at' => now()->addMonths(4),
            ]
        );
    }
}
