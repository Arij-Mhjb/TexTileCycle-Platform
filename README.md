# TexTileCycle

Plateforme Laravel 12 de circularité textile responsable. Elle relie consommateurs, marques, ateliers, associations et partenaires de recyclage pour tracer le cycle de vie d’un vêtement.

## Stack

- Laravel 12 et Eloquent
- Laravel Breeze pour l’authentification Blade
- Blade Components
- Tailwind CSS 3 et Alpine.js
- Chart.js pour les statistiques administrateur
- Thèmes clair et sombre persistants
- MySQL avec XAMPP en développement local
- PostgreSQL sur Render en production

## Modules CRUD

| Module | Entités et relation |
| --- | --- |
| Catalogue | Product ↔ Material, plusieurs-à-plusieurs |
| Smart Scan | Scan → Detection, un-à-plusieurs |
| Recyclage | RecoveryProgram → CollectionPoint, un-à-plusieurs |
| Collecte | ProductReturn → TreatmentResult, un-à-un |
| Conformité | Assessment → Evidence, un-à-plusieurs |

## Installation locale avec XAMPP et MySQL

### 1. Préparer XAMPP

1. Démarrer **Apache** et **MySQL** depuis le panneau XAMPP.
2. Ouvrir [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Créer une base nommée `textilecycle` avec l’interclassement `utf8mb4_unicode_ci`.

### 2. Installer le projet

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Sous Windows, si la commande `php` n’est pas reconnue :

```powershell
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate --seed
C:\xampp\php\php.exe artisan storage:link
C:\xampp\php\php.exe artisan serve
```

L’application locale sera accessible sur [http://127.0.0.1:8000](http://127.0.0.1:8000).

### Configuration MySQL locale

Le fichier `.env.example` utilise désormais :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=textilecycle
DB_USERNAME=root
DB_PASSWORD=
```

La configuration suppose l’utilisateur MySQL `root` sans mot de passe, valeur par défaut de nombreuses installations XAMPP locales. Si votre installation possède un mot de passe, renseignez uniquement votre fichier local `.env`.

## Production Render

Render continue d’utiliser PostgreSQL grâce aux variables définies dans son tableau de bord :

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://...
```

Le fichier `.env` local ne doit jamais être envoyé sur GitHub.

## Comptes de démonstration

Après le seeding :

```text
Administrateur : admin@textilecycle.test / password
Marque          : brand@textilecycle.test / password
Consommateur    : consumer@textilecycle.test / password
```

## Structure de l’interface

- `resources/views/layouts/app.blade.php` : layout du centre de pilotage administrateur
- `resources/views/components/consumer-layout.blade.php` : expérience dédiée aux consommateurs
- `resources/views/layouts/guest.blade.php` : layout d’authentification
- `resources/views/components/layout` : sidebar, header et footer
- `resources/views/components/ui` : champs, états, titres et actions communs

## Crédits visuels

- Logo TexTileCycle fourni par l’équipe du projet.
- Photographies de tri, don, eau, énergie et climat : Julia M Cameron, Burcu, Adrinil Dennis, NEOSiAM et Yeşim Çolak, via Pexels.
- Icônes officielles des ODD 6, 7, 12 et 13 : Nations Unies. Leur présence n’implique aucune approbation du contenu par l’ONU.

## Équipe

L’authentification, l’intégration front office/back office et le dépôt Git sont des tâches communes. Les cinq modules peuvent ensuite être répartis entre les membres de l’équipe.


## Catalogue et Smart Scan

Le catalogue public authentifié propose la recherche et les filtres par catégorie, origine, score de recyclabilité, caractère recyclable, réutilisable et réparable. Une fiche produit affiche la marque, la composition en pourcentage, l’origine et les consignes de fin de vie. Toute information absente est présentée comme « Non renseigné ».

Le Smart Scan accepte quatre entrées : code-barres, QR code, recherche manuelle et photo. Les correspondances exactes sur SKU, code-barres et QR obtiennent une confiance de 100 %. Les recherches textuelles peuvent retourner plusieurs candidats. Chaque résultat est conservé dans l’historique de l’utilisateur. La photo est stockée pour analyse sans inventer une reconnaissance lorsque aucun moteur OCR n’est configuré.

## Espace marque et preuves

Une inscription peut créer un compte consommateur ou marque. Une marque dispose de son espace `/brand`, gère uniquement ses produits et peut constituer des dossiers associés à des référentiels externes. La progression est calculée ainsi :

```text
étapes disposant d’une preuve approuvée / nombre total d’étapes × 100
```

À 100 %, le statut interne devient `verified_evidence`. TexTileCycle affiche « Preuves vérifiées » et ne se présente jamais comme organisme certificateur.

## Commandes de vérification

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build
```

Sur Render, conserver `DB_CONNECTION=pgsql`. En local XAMPP, conserver `DB_CONNECTION=mysql`. Les migrations sont compatibles avec les deux moteurs.


## Brand Studio professionnel

L’espace marque comprend désormais :

- un score de complétude calculé sur les données produit ;
- une composition obligatoirement égale à 100 % ;
- le workflow `brouillon → vérification → publication` ;
- les demandes de correction administrateur ;
- le versionnement des passeports publiés ;
- la duplication, l’archivage récupérable et l’import CSV ;
- un profil public de marque enrichi ;
- des preuves privées, versionnées et pondérées ;
- un dialogue traçable autour des preuves ;
- un journal d’activité et des notifications internes.

Les modifications d’un produit publié créent une nouvelle version et remettent le produit en brouillon. Une marque ne peut jamais publier directement un produit : elle le soumet, puis un administrateur le vérifie.

### Format d’import CSV

```csv
name,sku,category,description,origin,barcode,recyclability_score,recycling_instructions
T-shirt circulaire,TS-001,T-shirt,Description,Tunisie,6190000000001,85,Réemploi puis collecte
```

Les colonnes `name`, `sku` et `category` sont obligatoires. Les produits importés restent en brouillon.

### Vérification continue

La GitHub Action `.github/workflows/tests.yml` exécute automatiquement l’installation, le build front, les migrations SQLite et les tests Laravel sur chaque pull request et chaque mise à jour de `main`.
