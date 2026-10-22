<?php

namespace Database\Seeders;

use App\Models\RewardItem;
use Illuminate\Database\Seeder;

class ConsumerExperienceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Bon partenaire 10 TND', 'description' => 'Bon utilisable auprès d’un partenaire TexTileCycle participant.', 'points_cost' => 500, 'partner_name' => 'Réseau TexTileCycle', 'stock' => 100],
            ['name' => 'Réparation couture offerte', 'description' => 'Une réparation simple : bouton, ourlet ou petite couture.', 'points_cost' => 350, 'partner_name' => 'Atelier Seconde Vie', 'stock' => 50],
            ['name' => 'Bonus don solidaire', 'description' => 'Doublement des points sur votre prochain don textile.', 'points_cost' => 250, 'partner_name' => 'Textile Solidaire', 'stock' => null],
        ] as $item) {
            RewardItem::updateOrCreate(['name' => $item['name']], $item + ['is_active' => true]);
        }
    }
}
