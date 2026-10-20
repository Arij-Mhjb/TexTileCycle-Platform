<?php

namespace Database\Seeders;

use App\Models\RepairPartner;
use App\Models\RepairWorkshop;
use Illuminate\Database\Seeder;

class RepairWorkshopSeeder extends Seeder
{
    public function run(): void
    {
        $partners = RepairPartner::all();

        foreach ($partners as $partner) {
            $workshops = match ($partner->type) {
                'atelier' => [
                    ['name' => 'Atelier Vert - Retouches', 'city' => 'Tunis', 'address' => 'Rue de l\'Innovation, 15', 'specialties' => 'Retouches, réparations, renforts', 'contact_name' => 'Sonia Hmidi', 'capacity_per_week' => 20],
                    ['name' => 'Atelier Vert - Réemploi', 'city' => 'Tunis', 'address' => 'Avenue de la Liberté, 44', 'specialties' => 'Désassemblage, couture, revalorisation', 'contact_name' => 'Youssef Triki', 'capacity_per_week' => 15],
                ],
                'partenaire' => [
                    ['name' => 'Matière à Refaire - Sousse', 'city' => 'Sousse', 'address' => 'Avenue de la République, 27', 'specialties' => 'Tri, préparation, réparation légère', 'contact_name' => 'Nabil Riahi', 'capacity_per_week' => 18],
                ],
                default => [
                    ['name' => 'Thread Loop Collective - Sfax', 'city' => 'Sfax', 'address' => 'Zone industrielle 4, 8', 'specialties' => 'Couture locale, réparation créative', 'contact_name' => 'Mouna Kefi', 'capacity_per_week' => 22],
                ],
            };

            foreach ($workshops as $workshop) {
                RepairWorkshop::updateOrCreate(
                    ['repair_partner_id' => $partner->id, 'slug' => str($workshop['name'])->slug()->value()],
                    [
                        'repair_partner_id' => $partner->id,
                        'name' => $workshop['name'],
                        'slug' => str($workshop['name'])->slug()->value(),
                        'city' => $workshop['city'],
                        'address' => $workshop['address'],
                        'specialties' => $workshop['specialties'],
                        'description' => 'Atelier de démonstration pour le parcours de réparation textile de TexTileCycle.',
                        'contact_name' => $workshop['contact_name'],
                        'phone' => '+216 98 000 000',
                        'email' => 'atelier@example.com',
                        'capacity_per_week' => $workshop['capacity_per_week'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
