# TexTileCycle

Plateforme Laravel 12 de circularité textile responsable. Elle relie consommateurs, marques, ateliers, associations et partenaires de recyclage pour tracer le cycle de vie d’un vêtement.

## Stack

- Laravel 12 et Eloquent
- Laravel Breeze pour l’authentification Blade
- Blade Components
- Tailwind CSS 3 et Alpine.js
- Chart.js pour les statistiques administrateur
- Thèmes clair et sombre persistants
- SQLite par défaut, compatible MySQL

## Modules CRUD

| Module | Entités et relation |
| --- | --- |
| Catalogue | Product ↔ Material, plusieurs-à-plusieurs |
| Smart Scan | Scan → Detection, un-à-plusieurs |
| Recyclage | RecoveryProgram → CollectionPoint, un-à-plusieurs |
| Collecte | ProductReturn → TreatmentResult, un-à-un |
| Conformité | Assessment → Evidence, un-à-plusieurs |

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Compte de démonstration après le seeding : `demo@textilecycle.test` / `password`.

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
