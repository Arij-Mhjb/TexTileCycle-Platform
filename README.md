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
Administrateur : demo@textilecycle.test / password
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
