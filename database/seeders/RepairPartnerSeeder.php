<?php

namespace Database\Seeders;

use App\Models\RepairPartner;
use Illuminate\Database\Seeder;

class RepairPartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'name' => 'Atelier Vert',
                'type' => 'atelier',
                'city' => 'Tunis',
                'address' => 'Rue de l\'Innovation, 15',
                'description' => 'Atelier de réparation textile local dédié au renfort, retouche et valorisation de vêtements durables.',
                'contact_name' => 'Sonia Hmidi',
                'email' => 'contact@ateliervert.tn',
                'phone' => '+216 71 234 567',
                'website' => 'https://ateliervert.example',
                'is_active' => true,
            ],
            [
                'name' => 'Matière à Refaire',
                'type' => 'partenaire',
                'city' => 'Sousse',
                'address' => 'Avenue de la République, 27',
                'description' => 'Réseau de partenaires pour la collecte, le tri et la réutilisation des textiles usagés.',
                'contact_name' => 'Nabil Riahi',
                'email' => 'hello@matierearefaire.tn',
                'phone' => '+216 73 445 700',
                'website' => 'https://matierearefaire.example',
                'is_active' => true,
            ],
            [
                'name' => 'Thread Loop Collective',
                'type' => 'collectif',
                'city' => 'Sfax',
                'address' => 'Zone industrielle 4, 8',
                'description' => 'Collectif associatif qui relie couturiers, créateurs locaux et récupérateurs de textile.',
                'contact_name' => 'Mouna Kefi',
                'email' => 'team@threadloop.tn',
                'phone' => '+216 74 110 880',
                'website' => 'https://threadloop.example',
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partner) {
            RepairPartner::updateOrCreate(
                ['slug' => str($partner['name'])->slug()->value()],
                $partner,
            );
        }
    }
}
