<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $materials = [
            ['Coton conventionnel', 'plant', false, true, 'Fibre naturelle végétale couramment utilisée dans l’habillement.'],
            ['Polyester vierge', 'synthetic', false, true, 'Fibre synthétique issue de ressources fossiles.'],
            ['Polyamide / Nylon', 'synthetic', false, true, 'Fibre synthétique résistante souvent utilisée dans les vêtements techniques.'],
            ['Polyamide recyclé', 'recycled', false, true, 'Polyamide provenant de déchets pré ou post-consommation.'],
            ['Élasthanne', 'synthetic', false, false, 'Fibre élastique généralement utilisée en faible proportion.'],
            ['Viscose', 'regenerated', false, true, 'Fibre cellulosique régénérée issue de pâte de bois.'],
            ['Modal', 'regenerated', false, true, 'Fibre cellulosique régénérée souple et absorbante.'],
            ['Cupro', 'regenerated', false, true, 'Fibre cellulosique régénérée issue de linters de coton.'],
            ['Acétate', 'regenerated', false, false, 'Fibre artificielle dérivée de cellulose acétylée.'],
            ['Acrylique', 'synthetic', false, false, 'Fibre synthétique souvent utilisée comme alternative à la laine.'],
            ['Soie', 'animal', true, true, 'Fibre protéique animale produite par le ver à soie.'],
            ['Cachemire', 'animal', true, true, 'Fibre animale issue de chèvres cachemire.'],
            ['Alpaga', 'animal', true, true, 'Fibre animale issue de l’alpaga.'],
            ['Laine recyclée', 'recycled', true, true, 'Laine récupérée et retraitée à partir de déchets textiles.'],
            ['Jute', 'plant', false, true, 'Fibre végétale robuste et biodégradable sous conditions.'],
            ['Ramie', 'plant', false, true, 'Fibre végétale résistante proche du lin.'],
            ['Bambou viscose', 'regenerated', false, true, 'Fibre cellulosique régénérée utilisant du bambou comme matière première.'],
            ['Polyuréthane', 'synthetic', false, false, 'Polymère utilisé dans les enductions, mousses et alternatives au cuir.'],
        ];

        foreach ($materials as [$name, $type, $animalOrigin, $recyclable, $description]) {
            DB::table('materials')->updateOrInsert(
                ['name' => $name],
                compact('type', 'description') + [
                    'animal_origin' => $animalOrigin,
                    'recyclable' => $recyclable,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('materials')->whereIn('name', [
            'Coton conventionnel', 'Polyester vierge', 'Polyamide / Nylon', 'Polyamide recyclé',
            'Élasthanne', 'Viscose', 'Modal', 'Cupro', 'Acétate', 'Acrylique', 'Soie',
            'Cachemire', 'Alpaga', 'Laine recyclée', 'Jute', 'Ramie', 'Bambou viscose',
            'Polyuréthane',
        ])->delete();
    }
};
