<?php

namespace App\Services;

use App\Models\Label;

class RoadmapTemplates
{
    public function steps(Label $label): array
    {
        $name = mb_strtolower($label->name);
        $scope = ['Définir les produits et la chaîne de fabrication', 'Confirmer chaque référence, sa composition actuelle, les sites et les fournisseurs concernés.', 'Fiches techniques, compositions et liste des sites.'];
        $review = ['Préparer la revue externe', 'Vérifier les critères officiels en vigueur et traiter les écarts avant de contacter un organisme compétent.', 'Liste des écarts clôturés et dossier préparé pour l’organisme.'];
        if (str_contains($name, 'recycled') || str_contains($name, '(grs)') || str_contains($name, '(rcs)')) {
            return [$scope,
                ['Planifier la migration vers le contenu recyclé', 'Décrire la composition cible par produit et les substitutions prévues. Aucun pourcentage proposé ici ne vaut exigence officielle.', 'Spécifications révisées et plan de substitution par référence.'],
                ['Vérifier les fournisseurs et partenaires', 'Vérifier le périmètre, la validité et la chaîne de contrôle des documents de fournisseurs et de recycleurs.', 'Documents fournisseurs, certificats applicables et vérifications.'],
                ['Tracer les matières et leur collecte', 'Enregistrer l’origine, les lots, les poids et les transferts. Distinguer matière collectée et matière effectivement recyclée.', 'Factures, documents de transfert, photos et justificatifs de pesée.'],
                ['Documenter la transformation', 'Relier les lots entrants aux sorties du recycleur et expliquer les pertes.', 'Rapport de transformation, bilan matière et justificatifs du partenaire.'],
                ['Produire et contrôler un lot pilote', 'Indiquer la matière incorporée dans chaque produit et les résultats de contrôle.', 'Fiches de production, composition du lot et rapports de tests.'], $review];
        }
        if (str_contains($name, 'vegan')) {
            return [$scope,
                ['Inventorier tous les composants animaux', 'Examiner les fibres, cuir, colles, teintures, finitions et auxiliaires pour chaque référence.', 'Nomenclatures détaillées et déclarations fournisseurs.'],
                ['Remplacer les composants animaux', 'Documenter les alternatives choisies, les fournisseurs et la composition cible.', 'Fiches des alternatives et nomenclatures révisées.'],
                ['Vérifier les ingrédients et les pratiques de tests', 'Documenter les composants et les tests réalisés par la marque, pour elle ou sous son contrôle.', 'Déclarations fournisseurs et politique relative aux tests animaux.'],
                ['Maîtriser les mélanges et contaminations', 'Décrire séparation, nettoyage et contrôles sur les sites concernés.', 'Procédures et registres de contrôle.'],
                ['Valider les produits pilotes', 'Vérifier chaque référence de la nouvelle gamme et ses ingrédients.', 'Fiches finales et contrôles des produits pilotes.'], $review];
        }

        return [$scope,
            ['Identifier les critères et les écarts', 'Consulter le référentiel officiel pour le périmètre choisi et documenter ce qui manque.', 'Matrice des critères applicables et analyse des écarts.'],
            ['Adapter les matières et les fournisseurs', 'Décrire les changements nécessaires pour chaque produit et vérifier les documents fournisseurs.', 'Spécifications cibles et documents fournisseurs.'],
            ['Documenter les pratiques de fabrication', 'Consigner les pratiques environnementales, chimiques, sociales ou animales pertinentes pour ce label.', 'Procédures, mesures et audits applicables.'],
            ['Tester les produits et corriger les écarts', 'Documenter les essais pertinents et les corrections pour chaque référence.', 'Rapports de tests et actions correctives.'], $review];
    }
}
