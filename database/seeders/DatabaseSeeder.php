<?php

namespace Database\Seeders;

use App\Models\{
    Assessment,
    CollectionPoint,
    Detection,
    Evidence,
    Material,
    Product,
    ProductReturn,
    RecoveryProgram,
    Scan,
    TreatmentResult,
    User
};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'demo@textilecycle.test'],
            [
                'name' => 'Walaeddine Demo',
                'role' => 'admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $consumer = User::updateOrCreate(
            ['email' => 'consumer@textilecycle.test'],
            [
                'name' => 'Amira Ben Salah',
                'role' => 'consumer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $materialDefinitions = [
            'recycled_cotton' => ['name' => 'Coton recyclé', 'type' => 'recycled', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Fibre issue de déchets textiles pré-consommation et post-consommation.'],
            'organic_cotton' => ['name' => 'Coton biologique', 'type' => 'plant', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Coton cultivé avec des pratiques réduisant les intrants chimiques.'],
            'wool' => ['name' => 'Laine mérinos', 'type' => 'animal', 'animal_origin' => true, 'recyclable' => true, 'description' => 'Fibre animale nécessitant une traçabilité et des preuves de bien-être animal.'],
            'recycled_polyester' => ['name' => 'Polyester recyclé', 'type' => 'recycled', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Fibre synthétique issue majoritairement de matières plastiques recyclées.'],
            'linen' => ['name' => 'Lin européen', 'type' => 'plant', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Fibre végétale durable, résistante et biodégradable sous conditions.'],
            'hemp' => ['name' => 'Chanvre', 'type' => 'plant', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Fibre végétale robuste demandant peu d’eau lors de sa culture.'],
            'lyocell' => ['name' => 'Lyocell', 'type' => 'plant', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Fibre cellulosique produite en circuit de solvants majoritairement fermé.'],
            'leather' => ['name' => 'Cuir bovin', 'type' => 'animal', 'animal_origin' => true, 'recyclable' => false, 'description' => 'Matière animale à impact élevé nécessitant une traçabilité renforcée.'],
            'cactus' => ['name' => 'Alternative cuir de cactus', 'type' => 'plant', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Alternative végétale au cuir évaluée séparément pour sa teneur en polymères.'],
            'rubber' => ['name' => 'Caoutchouc recyclé', 'type' => 'recycled', 'animal_origin' => false, 'recyclable' => true, 'description' => 'Matière issue de rebuts industriels et de produits en fin de vie.'],
        ];

        $materials = [];
        foreach ($materialDefinitions as $key => $attributes) {
            $materials[$key] = Material::updateOrCreate(
                ['name' => $attributes['name']],
                $attributes
            );
        }

        $productDefinitions = [
            'jacket' => [
                'attributes' => ['name' => 'Winter Jacket 02', 'brand' => 'EcoFashion', 'sku' => 'EF-WJ-002', 'category' => 'Veste', 'description' => 'Veste chaude avec passeport textile complet et option de reprise.', 'status' => 'published', 'year' => 2026, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 79, 'circularity_score' => 88, 'animal_free_score' => 60, 'traceability_score' => 91],
                'materials' => ['recycled_polyester' => [60, 'Tunisie', true], 'wool' => [40, 'Nouvelle-Zélande', true]],
            ],
            'shirt' => [
                'attributes' => ['name' => 'ReLoop Shirt', 'brand' => 'Circular Studio', 'sku' => 'CS-RS-104', 'category' => 'Chemise', 'description' => 'Chemise légère conçue pour être réparée et recyclée.', 'status' => 'published', 'year' => 2026, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 86, 'circularity_score' => 92, 'animal_free_score' => 100, 'traceability_score' => 84],
                'materials' => ['recycled_cotton' => [70, 'Tunisie', true], 'linen' => [30, 'France', true]],
            ],
            'sneakers' => [
                'attributes' => ['name' => 'Ocean Runner', 'brand' => 'BlueStep', 'sku' => 'BS-OR-210', 'category' => 'Chaussures', 'description' => 'Sneakers démontables utilisant des composants recyclés.', 'status' => 'published', 'year' => 2026, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 82, 'circularity_score' => 86, 'animal_free_score' => 100, 'traceability_score' => 78],
                'materials' => ['recycled_polyester' => [65, 'Portugal', true], 'rubber' => [35, 'Espagne', true]],
            ],
            'coat' => [
                'attributes' => ['name' => 'Heritage Wool Coat', 'brand' => 'North Loom', 'sku' => 'NL-HC-330', 'category' => 'Manteau', 'description' => 'Manteau durable en laine certifiée avec service de réparation.', 'status' => 'published', 'year' => 2025, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 72, 'circularity_score' => 81, 'animal_free_score' => 15, 'traceability_score' => 94],
                'materials' => ['wool' => [85, 'Australie', true], 'recycled_polyester' => [15, 'Italie', true]],
            ],
            'bag' => [
                'attributes' => ['name' => 'Cactus City Bag', 'brand' => 'Verde Atelier', 'sku' => 'VA-CB-415', 'category' => 'Sac', 'description' => 'Sac sans matière animale conçu à partir d’une alternative au cuir.', 'status' => 'published', 'year' => 2026, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 84, 'circularity_score' => 79, 'animal_free_score' => 100, 'traceability_score' => 88],
                'materials' => ['cactus' => [80, 'Mexique', true], 'recycled_cotton' => [20, 'Tunisie', true]],
            ],
            'jeans' => [
                'attributes' => ['name' => 'Hemp Denim 2030', 'brand' => 'Terra Denim', 'sku' => 'TD-HD-2030', 'category' => 'Pantalon', 'description' => 'Jean à faible consommation d’eau et forte réparabilité.', 'status' => 'published', 'year' => 2026, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 90, 'circularity_score' => 93, 'animal_free_score' => 100, 'traceability_score' => 90],
                'materials' => ['hemp' => [55, 'France', true], 'organic_cotton' => [45, 'Tunisie', true]],
            ],
            'dress' => [
                'attributes' => ['name' => 'Lyocell Flow Dress', 'brand' => 'Nova Fibers', 'sku' => 'NF-LD-520', 'category' => 'Robe', 'description' => 'Robe fluide mono-dominante favorisant la recyclabilité.', 'status' => 'published', 'year' => 2026, 'repairable' => true, 'reusable' => true, 'recyclable' => true, 'environmental_score' => 88, 'circularity_score' => 85, 'animal_free_score' => 100, 'traceability_score' => 92],
                'materials' => ['lyocell' => [90, 'Autriche', true], 'recycled_cotton' => [10, 'Tunisie', true]],
            ],
            'leather_boots' => [
                'attributes' => ['name' => 'Classic Leather Boots', 'brand' => 'Heritage Walk', 'sku' => 'HW-LB-610', 'category' => 'Chaussures', 'description' => 'Produit témoin permettant de comparer cuir animal et alternatives responsables.', 'status' => 'published', 'year' => 2025, 'repairable' => true, 'reusable' => true, 'recyclable' => false, 'environmental_score' => 48, 'circularity_score' => 55, 'animal_free_score' => 0, 'traceability_score' => 61],
                'materials' => ['leather' => [75, 'Italie', false], 'rubber' => [25, 'Espagne', true]],
            ],
        ];

        $products = [];
        foreach ($productDefinitions as $key => $definition) {
            $products[$key] = Product::updateOrCreate(
                ['sku' => $definition['attributes']['sku']],
                $definition['attributes']
            );

            $pivot = [];
            foreach ($definition['materials'] as $materialKey => [$percentage, $origin, $certified]) {
                $pivot[$materials[$materialKey]->id] = [
                    'percentage' => $percentage,
                    'origin_country' => $origin,
                    'certified' => $certified,
                ];
            }
            $products[$key]->materials()->sync($pivot);
        }

        $programDefinitions = [
            'take_back' => [
                'attributes' => ['name' => 'Reprise textile toutes marques', 'organization_name' => 'EcoFashion Tunis', 'type' => 'take_back', 'description' => 'Collecte des vêtements pour tri, réemploi et recyclage.', 'accepted_materials' => 'Coton, laine, polyester, lin, lyocell', 'reward_points' => 150, 'is_active' => true, 'starts_at' => now()->startOfYear(), 'ends_at' => null],
                'points' => [
                    'lac2' => ['name' => 'EcoFashion Lac 2', 'address' => 'Rue de la Bourse, Les Berges du Lac 2', 'city' => 'Tunis', 'latitude' => 36.8487, 'longitude' => 10.2685, 'opening_hours' => 'Lun–Sam · 09:00–19:00', 'is_active' => true],
                    'manar' => ['name' => 'EcoFashion El Manar', 'address' => 'Centre commercial El Manar', 'city' => 'Tunis', 'latitude' => 36.8398, 'longitude' => 10.1581, 'opening_hours' => 'Lun–Dim · 10:00–20:00', 'is_active' => true],
                ],
            ],
            'repair' => [
                'attributes' => ['name' => 'Repair First', 'organization_name' => 'Atelier Seconde Vie', 'type' => 'repair', 'description' => 'Diagnostic, couture, remplacement de pièces et remise en état.', 'accepted_materials' => 'Denim, coton, laine, lin', 'reward_points' => 90, 'is_active' => true, 'starts_at' => now()->subMonths(4), 'ends_at' => null],
                'points' => [
                    'ariana' => ['name' => 'Atelier Seconde Vie Ariana', 'address' => 'Avenue Hédi Nouira, Ennasr 2', 'city' => 'Ariana', 'latitude' => 36.8665, 'longitude' => 10.1647, 'opening_hours' => 'Mar–Sam · 09:30–18:30', 'is_active' => true],
                ],
            ],
            'donation' => [
                'attributes' => ['name' => 'Don & Réemploi Solidaire', 'organization_name' => 'Textile Solidaire', 'type' => 'donation', 'description' => 'Collecte, contrôle qualité et redistribution de vêtements réutilisables.', 'accepted_materials' => 'Tous textiles propres et réutilisables', 'reward_points' => 110, 'is_active' => true, 'starts_at' => now()->subYear(), 'ends_at' => null],
                'points' => [
                    'sousse' => ['name' => 'Maison du Réemploi Sousse', 'address' => 'Avenue de la République', 'city' => 'Sousse', 'latitude' => 35.8256, 'longitude' => 10.6369, 'opening_hours' => 'Lun–Ven · 08:30–17:00', 'is_active' => true],
                    'sfax' => ['name' => 'Hub Solidaire Sfax', 'address' => 'Route de Tunis km 3', 'city' => 'Sfax', 'latitude' => 34.7538, 'longitude' => 10.7460, 'opening_hours' => 'Lun–Sam · 09:00–18:00', 'is_active' => true],
                ],
            ],
            'footwear' => [
                'attributes' => ['name' => 'Footwear Loop', 'organization_name' => 'BlueStep', 'type' => 'recycling', 'description' => 'Reprise de chaussures pour séparation des semelles, textiles et accessoires.', 'accepted_materials' => 'Chaussures textiles, caoutchouc, polyester', 'reward_points' => 180, 'is_active' => true, 'starts_at' => now()->subMonths(2), 'ends_at' => now()->addYear()],
                'points' => [
                    'marsacube' => ['name' => 'BlueStep Marsa Cube', 'address' => 'La Marsa Cube', 'city' => 'La Marsa', 'latitude' => 36.8869, 'longitude' => 10.3266, 'opening_hours' => 'Lun–Dim · 10:00–20:00', 'is_active' => true],
                    'nabeul' => ['name' => 'BlueStep Nabeul', 'address' => 'Avenue Habib Bourguiba', 'city' => 'Nabeul', 'latitude' => 36.4513, 'longitude' => 10.7352, 'opening_hours' => 'Lun–Sam · 09:00–19:00', 'is_active' => true],
                ],
            ],
        ];

        $programs = [];
        $points = [];
        foreach ($programDefinitions as $programKey => $definition) {
            $programs[$programKey] = RecoveryProgram::updateOrCreate(
                ['name' => $definition['attributes']['name'], 'organization_name' => $definition['attributes']['organization_name']],
                $definition['attributes']
            );

            foreach ($definition['points'] as $pointKey => $pointAttributes) {
                $points[$pointKey] = CollectionPoint::updateOrCreate(
                    ['recovery_program_id' => $programs[$programKey]->id, 'name' => $pointAttributes['name']],
                    array_merge($pointAttributes, ['recovery_program_id' => $programs[$programKey]->id])
                );
            }
        }

        $scanDefinitions = [
            ['key' => 'scan_jacket', 'user' => $admin, 'source_type' => 'label', 'product' => 'jacket', 'raw_text' => 'EcoFashion Winter Jacket EF-WJ-002 60% recycled polyester 40% merino wool', 'confidence' => 94.50, 'days' => 35, 'composition' => '60 % polyester recyclé, 40 % laine mérinos'],
            ['key' => 'scan_shirt', 'user' => $consumer, 'source_type' => 'receipt', 'product' => 'shirt', 'raw_text' => 'Circular Studio ReLoop Shirt CS-RS-104', 'confidence' => 96.20, 'days' => 28, 'composition' => '70 % coton recyclé, 30 % lin européen'],
            ['key' => 'scan_sneakers', 'user' => $consumer, 'source_type' => 'barcode', 'product' => 'sneakers', 'raw_text' => 'BlueStep Ocean Runner BS-OR-210', 'confidence' => 98.10, 'days' => 21, 'composition' => '65 % polyester recyclé, 35 % caoutchouc recyclé'],
            ['key' => 'scan_bag', 'user' => $admin, 'source_type' => 'qr_code', 'product' => 'bag', 'raw_text' => 'Verde Atelier Cactus City Bag VA-CB-415', 'confidence' => 99.00, 'days' => 14, 'composition' => '80 % alternative cuir de cactus, 20 % coton recyclé'],
            ['key' => 'scan_jeans', 'user' => $consumer, 'source_type' => 'label', 'product' => 'jeans', 'raw_text' => 'Terra Denim Hemp Denim 2030 TD-HD-2030', 'confidence' => 93.80, 'days' => 7, 'composition' => '55 % chanvre, 45 % coton biologique'],
            ['key' => 'scan_boots', 'user' => $admin, 'source_type' => 'receipt', 'product' => 'leather_boots', 'raw_text' => 'Heritage Walk Classic Leather Boots HW-LB-610', 'confidence' => 91.40, 'days' => 2, 'composition' => '75 % cuir bovin, 25 % caoutchouc recyclé'],
        ];

        foreach ($scanDefinitions as $definition) {
            $product = $products[$definition['product']];
            $scan = Scan::updateOrCreate(
                ['user_id' => $definition['user']->id, 'raw_text' => $definition['raw_text']],
                ['source_type' => $definition['source_type'], 'status' => 'completed', 'confidence' => $definition['confidence'], 'scanned_at' => now()->subDays($definition['days'])]
            );
            Detection::updateOrCreate(
                ['scan_id' => $scan->id, 'detected_name' => $product->name],
                ['product_id' => $product->id, 'detected_brand' => $product->brand, 'detected_category' => $product->category, 'detected_composition' => $definition['composition'], 'confidence' => $definition['confidence'], 'is_confirmed' => true]
            );
        }

        $returnDefinitions = [
            ['reference' => 'RET-2026-0001', 'user' => $admin, 'product' => 'jacket', 'point' => 'lac2', 'status' => 'processed', 'weight' => 1.20, 'days' => 30, 'result' => [60, 25, 15], 'notes' => 'Produit en bon état, réemploi prioritaire.'],
            ['reference' => 'RET-2026-0002', 'user' => $consumer, 'product' => 'shirt', 'point' => 'sousse', 'status' => 'processed', 'weight' => 0.35, 'days' => 24, 'result' => [20, 75, 5], 'notes' => 'Chemise réemployée après contrôle qualité.'],
            ['reference' => 'RET-2026-0003', 'user' => $consumer, 'product' => 'sneakers', 'point' => 'marsacube', 'status' => 'processed', 'weight' => 0.82, 'days' => 18, 'result' => [78, 12, 10], 'notes' => 'Semelles et textile séparés pour recyclage.'],
            ['reference' => 'RET-2026-0004', 'user' => $admin, 'product' => 'coat', 'point' => 'ariana', 'status' => 'processed', 'weight' => 1.45, 'days' => 12, 'result' => [10, 88, 2], 'notes' => 'Doublure réparée et manteau remis en circulation.'],
            ['reference' => 'RET-2026-0005', 'user' => $consumer, 'product' => 'bag', 'point' => 'manar', 'status' => 'received', 'weight' => 0.58, 'days' => 6, 'result' => null, 'notes' => 'En attente du diagnostic de réparabilité.'],
            ['reference' => 'RET-2026-0006', 'user' => $consumer, 'product' => 'jeans', 'point' => 'sfax', 'status' => 'declared', 'weight' => 0.72, 'days' => 1, 'result' => null, 'notes' => 'Retour déclaré par le consommateur.'],
            ['reference' => 'RET-2026-0007', 'user' => $admin, 'product' => 'leather_boots', 'point' => 'nabeul', 'status' => 'processed', 'weight' => 1.10, 'days' => 40, 'result' => [45, 40, 15], 'notes' => 'Composants récupérables séparés du cuir endommagé.'],
        ];

        foreach ($returnDefinitions as $definition) {
            $return = ProductReturn::updateOrCreate(
                ['reference' => $definition['reference']],
                [
                    'user_id' => $definition['user']->id,
                    'product_id' => $products[$definition['product']]->id,
                    'collection_point_id' => $points[$definition['point']]->id,
                    'status' => $definition['status'],
                    'weight_kg' => $definition['weight'],
                    'returned_at' => now()->subDays($definition['days']),
                    'notes' => $definition['notes'],
                ]
            );

            if ($definition['result']) {
                [$recycled, $reused, $waste] = $definition['result'];
                TreatmentResult::updateOrCreate(
                    ['product_return_id' => $return->id],
                    ['recycled_percent' => $recycled, 'reused_percent' => $reused, 'waste_percent' => $waste, 'processed_at' => now()->subDays(max(0, $definition['days'] - 2)), 'notes' => 'Résultat vérifié par le partenaire de traitement.']
                );
            }
        }

        $assessmentDefinitions = [
            ['organization' => 'EcoFashion', 'framework' => 'ISO 14001', 'status' => 'in_progress', 'score' => 81, 'days' => 45, 'review_months' => 6, 'notes' => 'Objectif eau annuel et analyse des fournisseurs à compléter.', 'evidence' => [['Rapport annuel de collecte 2026', 'report', 'verified', 12], ['Registre des consommations d’eau', 'dataset', 'pending', 8]]],
            ['organization' => 'Circular Studio', 'framework' => 'Textile Circularity', 'status' => 'completed', 'score' => 92, 'days' => 80, 'review_months' => 12, 'notes' => 'Excellente réparabilité et forte part de matières recyclées.', 'evidence' => [['Passeport matières ReLoop', 'traceability', 'verified', 18], ['Rapport de réparabilité', 'audit', 'verified', 10]]],
            ['organization' => 'North Loom', 'framework' => 'Responsible Wool Standard', 'status' => 'in_progress', 'score' => 87, 'days' => 25, 'review_months' => 6, 'notes' => 'Traçabilité de la laine élevée, contrôle documentaire en cours.', 'evidence' => [['Certificat fournisseur laine', 'certificate', 'verified', 9], ['Audit bien-être animal', 'audit', 'pending', 5]]],
            ['organization' => 'BlueStep', 'framework' => 'ISO 14001', 'status' => 'draft', 'score' => 69, 'days' => 12, 'review_months' => 4, 'notes' => 'Formaliser les objectifs énergie et les résultats de fin de vie.', 'evidence' => [['Bilan Footwear Loop', 'report', 'pending', 7], ['Traçabilité caoutchouc', 'traceability', 'pending', 6]]],
            ['organization' => 'Verde Atelier', 'framework' => 'Animal-Free Readiness', 'status' => 'completed', 'score' => 89, 'days' => 55, 'review_months' => 12, 'notes' => 'Composition sans matière animale vérifiée; analyse polymères disponible.', 'evidence' => [['Déclaration de composition', 'certificate', 'verified', 15], ['Analyse environnementale cactus', 'report', 'verified', 11]]],
        ];

        foreach ($assessmentDefinitions as $definition) {
            $assessmentDate = now()->subDays($definition['days'])->toDateString();
            $assessment = Assessment::updateOrCreate(
                ['organization_name' => $definition['organization'], 'framework' => $definition['framework']],
                ['status' => $definition['status'], 'readiness_score' => $definition['score'], 'assessment_date' => $assessmentDate, 'next_review_date' => now()->addMonths($definition['review_months'])->toDateString(), 'notes' => $definition['notes']]
            );

            foreach ($definition['evidence'] as [$title, $type, $status, $expiryMonths]) {
                Evidence::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'title' => $title],
                    ['type' => $type, 'status' => $status, 'expires_at' => now()->addMonths($expiryMonths)->toDateString(), 'notes' => 'Donnée de démonstration TexTileCycle.']
                );
            }
        }
    }
}
