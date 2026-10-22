<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['recycled-claim-standard-rcs', 'Recycled Claim Standard (RCS)', 'Textile Exchange', 'circularity', 'Démarche relative au contenu recyclé et à sa chaîne de contrôle. La recyclabilité seule ne prouve pas l’éligibilité.', 'https://www.textileexchange.org/recycled-claim-global-recycled-standard/'],
            ['eu-ecolabel-textiles', 'EU Ecolabel — Textiles', 'Commission européenne', 'environment', 'Démarche couvrant notamment les fibres, substances, procédés de fabrication et la durabilité des produits textiles.', 'https://green-forum.ec.europa.eu/green-business/eu-ecolabel/product-groups-and-criteria/clothing-and-textiles_en'],
            ['vegan-trademark', 'Vegan Trademark', 'The Vegan Society', 'animals', 'Démarche pour des produits sans composants animaux et sans tests sur animaux réalisés par la marque, pour elle ou sous son contrôle. Tous les ingrédients et auxiliaires doivent être vérifiés.', 'https://www.vegansociety.com/trademark/vegan-trademark-standards'],
        ] as [$slug, $name, $organization, $pillar, $description, $url]) {
            if (DB::table('labels')->where('slug', $slug)->exists()) {
                continue;
            }
            $id = DB::table('labels')->insertGetId(['slug' => $slug, 'name' => $name, 'organization' => $organization,
                'pillar' => $pillar, 'description' => $description, 'official_url' => $url,
                'cost_hint' => 'Tarification communiquée par l’organisme officiel', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            foreach (['Définir la gamme et vérifier le référentiel officiel en vigueur', 'Documenter la composition et les fournisseurs',
                'Identifier les écarts et réunir les tests et justificatifs', 'Préparer la revue auprès de l’organisme officiel'] as $position => $title) {
                DB::table('label_steps')->insert(['label_id' => $id, 'title' => $title, 'position' => $position + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Preserve labels and any dossiers created by brands.
    }
};
