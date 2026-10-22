<?php

namespace Database\Seeders;

use App\Models\ChatbotCategory;
use App\Models\ChatbotQuestion;
use Illuminate\Database\Seeder;

class ChatbotSeeder extends Seeder
{
    public function run(): void
    {
        $categoryDefinitions = [
            'compte' => ['name' => 'Compte & général', 'description' => 'Inscription, tarifs et fonctionnement général de la plateforme.'],
            'scan' => ['name' => 'Smart Scan & passeport', 'description' => 'Scanner un produit et consulter son passeport textile.'],
            'retours' => ['name' => 'Retours & collecte', 'description' => 'Déposer un vêtement usagé et trouver un point de collecte.'],
            'reparation' => ['name' => 'Réparation & seconde vie', 'description' => 'Faire réparer, revendre ou échanger un produit.'],
            'recyclage' => ['name' => 'Recyclage & impact', 'description' => 'Comprendre le recyclage des matières et l’impact environnemental.'],
            'marques' => ['name' => 'Marques, labels & fournisseurs', 'description' => 'Inscription des marques, labels écologiques et traçabilité.'],
            'recompenses' => ['name' => 'Récompenses & communauté', 'description' => 'Points fidélité, réclamations et blog de sensibilisation.'],
        ];

        $categories = [];
        foreach ($categoryDefinitions as $key => $attributes) {
            $categories[$key] = ChatbotCategory::updateOrCreate(['name' => $attributes['name']], $attributes);
        }

        $questionDefinitions = [
            ['category' => 'compte', 'question' => 'Comment créer un compte sur TexTileCycle ?', 'keywords' => 'inscription, créer compte, s\'inscrire', 'answer' => 'Cliquez sur « Créer un compte » en haut de la page d\'accueil, renseignez votre nom, votre e-mail et un mot de passe. Un e-mail de confirmation vous sera envoyé.'],
            ['category' => 'compte', 'question' => 'Comment réinitialiser mon mot de passe ?', 'keywords' => 'mot de passe oublié, réinitialiser, reset password', 'answer' => 'Depuis la page de connexion, cliquez sur « Mot de passe oublié » et suivez les instructions reçues par e-mail pour en créer un nouveau.'],
            ['category' => 'compte', 'question' => 'TexTileCycle est-il gratuit ?', 'keywords' => 'gratuit, prix, tarif, payant', 'answer' => 'Le service est entièrement gratuit pour les particuliers (scan, retours, réparation, récompenses). Les marques disposent d\'un espace professionnel avec inscription validée par notre équipe.'],
            ['category' => 'compte', 'question' => 'Quels rôles existent sur la plateforme ?', 'keywords' => 'rôle, consommateur, marque, collecteur, admin', 'answer' => 'Quatre profils existent : consommateur (scan, retours, récompenses), marque (passeports produits, roadmaps), collecteur (gestion des points de collecte) et administrateur (pilotage global).'],

            ['category' => 'scan', 'question' => 'Comment scanner un produit ?', 'keywords' => 'scanner, scan, qr code, étiquette', 'answer' => 'Utilisez le Smart Scan depuis votre espace : prenez en photo l\'étiquette, le ticket ou le QR code du produit. Notre moteur de détection identifie automatiquement la matière et la marque.'],
            ['category' => 'scan', 'question' => 'Qu\'est-ce qu\'un passeport produit ?', 'keywords' => 'passeport, passeport textile, historique produit', 'answer' => 'Le passeport produit regroupe la composition matière, l\'origine, les instructions d\'entretien et l\'historique de traçabilité fournisseur d\'un vêtement, accessible via son Smart Scan.'],
            ['category' => 'scan', 'question' => 'Comment savoir si un vêtement est recyclable ?', 'keywords' => 'recyclable, recyclabilité, label écologique', 'answer' => 'Après un scan, la fiche produit affiche un score de circularité ainsi que les labels écologiques associés qui indiquent sa recyclabilité.'],
            ['category' => 'scan', 'question' => 'Le scan fonctionne-t-il sans connexion caméra ?', 'keywords' => 'caméra indisponible, saisie manuelle, scanner sans caméra', 'answer' => 'Si votre navigateur ne permet pas d\'accéder à la caméra, vous pouvez saisir manuellement le code ou les informations du produit dans le formulaire de scan.'],

            ['category' => 'retours', 'question' => 'Comment retourner un vêtement usagé ?', 'keywords' => 'retour, don, vêtement usagé, déclarer un retour', 'answer' => 'Depuis votre espace, déclarez un retour en indiquant le produit, son poids approximatif et le point de collecte choisi. Vous recevez ensuite une référence de suivi.'],
            ['category' => 'retours', 'question' => 'Où se trouve le point de collecte le plus proche ?', 'keywords' => 'point de collecte, localisation, carte', 'answer' => 'La page des points de collecte affiche les emplacements disponibles ; vous pouvez activer la géolocalisation pour trouver le point le plus proche de vous.'],
            ['category' => 'retours', 'question' => 'Qui récupère mes vêtements déposés ?', 'keywords' => 'collecteur, qui récupère, ramassage', 'answer' => 'Les collecteurs partenaires, rattachés à chaque programme de récupération, gèrent la collecte et le suivi des retours déposés en point de collecte.'],
            ['category' => 'retours', 'question' => 'Que deviennent mes vêtements après le retour ?', 'keywords' => 'statut retour, suivi, traitement', 'answer' => 'Chaque retour passe par les étapes déclaré, reçu, tri, traité. Vous pouvez suivre ce statut à tout moment depuis votre historique de retours.'],
            ['category' => 'retours', 'question' => 'Puis-je annuler un retour déclaré ?', 'keywords' => 'annuler retour, modifier retour', 'answer' => 'Un retour peut être modifié tant qu\'il est au statut « déclaré ». Contactez le point de collecte concerné pour toute annulation après ce stade.'],

            ['category' => 'reparation', 'question' => 'Puis-je faire réparer un produit ?', 'keywords' => 'réparation, réparer, atelier', 'answer' => 'Oui, déposez une demande de réparation en précisant le produit et le défaut constaté. Vous pourrez choisir un atelier partenaire ou un centre de réparation proche de vous.'],
            ['category' => 'reparation', 'question' => 'Combien coûte une réparation ?', 'keywords' => 'prix réparation, coût, devis', 'answer' => 'Le tarif dépend de l\'atelier et du type de réparation. Un devis personnalisé vous est communiqué après l\'examen de votre demande.'],
            ['category' => 'reparation', 'question' => 'Qu\'est-ce que la Marketplace de seconde main ?', 'keywords' => 'marketplace, revente, seconde main, occasion', 'answer' => 'La Marketplace permet de proposer à la revente des vêtements en bon état, dans une logique de prolongation de leur durée de vie.'],
            ['category' => 'reparation', 'question' => 'Comment fonctionne la reprise d\'un vêtement (trade-in) ?', 'keywords' => 'reprise, trade-in, échange', 'answer' => 'La reprise permet d\'échanger un vêtement usagé contre des points de récompense ou un avantage auprès des marques partenaires participantes.'],
            ['category' => 'reparation', 'question' => 'Quelle différence entre un atelier partenaire et un centre de réparation ?', 'keywords' => 'atelier partenaire, centre de réparation, différence', 'answer' => 'Les centres de réparation sont des structures internes référencées sur la plateforme, tandis que les ateliers partenaires sont des professionnels indépendants affiliés à TexTileCycle.'],

            ['category' => 'recyclage', 'question' => 'Comment sont traitées les matières collectées ?', 'keywords' => 'traitement matière, filière recyclage', 'answer' => 'Chaque retour traité génère un résultat de traitement détaillant les pourcentages recyclés, réemployés et perdus, selon la filière adaptée à la matière.'],
            ['category' => 'recyclage', 'question' => 'Qu\'est-ce que l\'impact environnemental affiché sur mon profil ?', 'keywords' => 'impact environnemental, rapport esg, bilan carbone', 'answer' => 'Nos rapports d\'impact calculent, par catégorie et par matière, les bénéfices environnementaux (eau économisée, CO2 évité) générés par vos retours et réparations.'],
            ['category' => 'recyclage', 'question' => 'Comment identifier la fibre d\'un vêtement ?', 'keywords' => 'identifier fibre, reconnaître matière, composition', 'answer' => 'L\'outil de reconnaissance de matière analyse votre vêtement et propose une estimation de sa composition textile dominante.'],
            ['category' => 'recyclage', 'question' => 'Qu\'est-ce que le greenwashing et comment le détecter ?', 'keywords' => 'greenwashing, allégation écologique, analyse impact', 'answer' => 'Le greenwashing désigne des allégations écologiques exagérées ou trompeuses. Notre analyseur d\'allégations vertes évalue la cohérence des discours environnementaux des marques.'],
            ['category' => 'recyclage', 'question' => 'Toutes les matières sont-elles recyclables de la même façon ?', 'keywords' => 'matière recyclable, mélange fibre, coton polyester', 'answer' => 'Non, les matières pures comme le coton ou le polyester se recyclent plus facilement que les mélanges de fibres, ce qui influence le taux de recyclabilité d\'un vêtement.'],

            ['category' => 'marques', 'question' => 'Comment une marque s\'inscrit-elle sur la plateforme ?', 'keywords' => 'inscription marque, candidature marque', 'answer' => 'Une marque dépose une demande d\'inscription avec ses informations ; notre équipe l\'examine avant validation et activation de l\'espace marque.'],
            ['category' => 'marques', 'question' => 'Que signifient les labels écologiques affichés sur un produit ?', 'keywords' => 'label écologique, certification, signification label', 'answer' => 'Les labels écologiques certifient des engagements précis (origine des matières, absence de substances nocives, conditions de production) vérifiés par notre référentiel textile.'],
            ['category' => 'marques', 'question' => 'Comment la traçabilité des lots de matière est-elle vérifiée ?', 'keywords' => 'traçabilité, lot, fournisseur, conformité', 'answer' => 'Chaque lot fourni par un fournisseur est associé à des preuves de conformité, contrôlées par notre équipe via les dossiers de traçabilité des lots.'],
            ['category' => 'marques', 'question' => 'Comment une marque propose-t-elle un nouveau passeport produit ?', 'keywords' => 'proposition passeport, validation produit', 'answer' => 'Les marques soumettent une proposition de passeport produit depuis leur espace ; elle est examinée et validée par notre équipe avant publication.'],

            ['category' => 'recompenses', 'question' => 'Comment gagner des points de récompense ?', 'keywords' => 'points, récompense, fidélité, gagner points', 'answer' => 'Vous gagnez des points à chaque retour traité, réparation effectuée ou participation à un programme de récupération, échangeables contre des récompenses du catalogue.'],
            ['category' => 'recompenses', 'question' => 'Comment déposer une réclamation ?', 'keywords' => 'réclamation, litige, problème commande', 'answer' => 'Depuis votre espace consommateur, ouvrez une réclamation en décrivant votre problème ; notre équipe vous répond directement sur le dossier.'],
            ['category' => 'recompenses', 'question' => 'Où lire les articles de sensibilisation ?', 'keywords' => 'blog, articles, sensibilisation, conseils', 'answer' => 'Notre blog propose des articles sur le réemploi, le recyclage, l\'impact environnemental et la réparation, accessibles depuis le menu « Blog ».'],
            ['category' => 'recompenses', 'question' => 'Puis-je exporter mes données personnelles ?', 'keywords' => 'export données, rgpd, télécharger mes données', 'answer' => 'Oui, un export de vos données personnelles est disponible depuis votre espace consommateur, conformément à la réglementation RGPD.'],
        ];

        foreach ($questionDefinitions as $position => $definition) {
            ChatbotQuestion::updateOrCreate(
                ['question' => $definition['question']],
                [
                    'chatbot_category_id' => $categories[$definition['category']]->id,
                    'keywords' => $definition['keywords'],
                    'answer' => $definition['answer'],
                    'is_active' => true,
                    'position' => $position,
                ]
            );
        }
    }
}
