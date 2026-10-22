<?php

namespace Tests\Support;

trait BrandQuestionnaireData
{
    protected function brandAnswers(array $overrides = []): array
    {
        return array_replace([
            'country' => 'Tunisie', 'activity' => 'both', 'products' => 'T-shirts et vestes',
            'markets' => 'Tunisie et France', 'sites' => 'Assemblage en Tunisie, fournisseurs de tissus en France',
            'materials' => 'T-shirt : 100 % coton, fournisseur à documenter',
            'recycled' => 'no', 'recycled_plan' => 'yes', 'sorting' => 'Collecte et tri par matière',
            'organic' => 'no', 'animal' => 'no', 'animal_tests' => 'no',
            'vegan_controls' => 'Déclarations fournisseurs pour les fibres, colles et finitions',
            'chemicals' => 'no', 'water_energy' => 'no', 'durability' => 'Tests de lavage à préparer',
            'social' => 'no', 'traceability' => 'Factures et fiches de composition',
            'certifications' => 'Aucune', 'goals' => 'Définir une gamme certifiable sur 12 mois',
        ], $overrides);
    }
}
