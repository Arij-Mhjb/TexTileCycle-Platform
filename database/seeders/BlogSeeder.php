<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('role', 'admin')->first();

        $categoryDefinitions = [
            'reemploi' => ['name' => 'Réemploi', 'description' => 'Conseils pour prolonger la vie de vos vêtements.'],
            'recyclage' => ['name' => 'Recyclage textile', 'description' => 'Comprendre les filières de recyclage des matières.'],
            'impact' => ['name' => 'Impact environnemental', 'description' => 'Décryptage des enjeux écologiques de la mode.'],
            'reparation' => ['name' => 'Réparation', 'description' => 'Astuces et retours d’expérience sur la réparation textile.'],
        ];

        $categories = [];
        foreach ($categoryDefinitions as $key => $attributes) {
            $categories[$key] = Category::updateOrCreate(
                ['name' => $attributes['name']],
                $attributes
            );
        }

        $articleDefinitions = [
            [
                'category' => 'reemploi',
                'title' => 'Donner une seconde vie à sa garde-robe',
                'excerpt' => 'Les bonnes pratiques pour trier, donner et revaloriser ses vêtements avant de les jeter.',
                'content' => "Avant de jeter un vêtement, plusieurs options existent : le don, la revente, l'échange entre particuliers ou le dépôt en point de collecte.\n\nCet article détaille les critères pour choisir la meilleure filière selon l'état du vêtement et vous aide à réduire votre impact environnemental tout en donnant une seconde chance à vos pièces.",
                'days' => 10,
            ],
            [
                'category' => 'recyclage',
                'title' => 'Comment fonctionne le recyclage des fibres textiles',
                'excerpt' => 'Du tri au fil régénéré : le parcours d’un textile recyclé expliqué étape par étape.',
                'content' => "Le recyclage textile repose sur plusieurs étapes : collecte, tri par matière, effilochage puis transformation en nouvelle fibre.\n\nCertaines matières comme le coton ou le polyester se recyclent plus facilement que les mélanges, ce qui explique pourquoi la composition d'un vêtement influence directement sa recyclabilité.",
                'days' => 25,
            ],
            [
                'category' => 'impact',
                'title' => 'L’empreinte carbone d’un vêtement, de la fibre au dressing',
                'excerpt' => 'Un tour d’horizon des étapes qui pèsent le plus dans le bilan carbone d’un textile.',
                'content' => "La production de fibres, la teinture, le transport et l'entretien représentent les principales sources d'émissions d'un vêtement.\n\nComprendre ces étapes permet d'identifier les leviers les plus efficaces pour réduire son impact, aussi bien côté marque que côté consommateur.",
                'days' => 5,
            ],
            [
                'category' => 'reparation',
                'title' => '5 réparations simples à réaliser soi-même',
                'excerpt' => 'Boutons, ourlets, fermetures éclair : les gestes de base pour éviter de jeter trop vite.',
                'content' => "Avec quelques outils de base (fil, aiguille, pince), il est possible de réparer soi-même la majorité des petites dégradations courantes.\n\nCet article présente cinq réparations accessibles aux débutants, avant de faire appel à un atelier de réparation professionnel pour les cas plus complexes.",
                'days' => 2,
            ],
            [
                'category' => 'reemploi',
                'title' => 'Organiser un vide-dressing solidaire',
                'excerpt' => 'Les étapes clés pour transformer un tri de dressing en action solidaire et écoresponsable.',
                'content' => "Un vide-dressing solidaire permet de redistribuer des vêtements encore en bon état à des associations locales.\n\nCe guide pratique couvre la préparation, la communication et le suivi d'un événement de ce type, à l'échelle d'un quartier ou d'une entreprise.",
                'days' => null,
            ],
        ];

        foreach ($articleDefinitions as $definition) {
            Article::updateOrCreate(
                ['title' => $definition['title']],
                [
                    'category_id' => $categories[$definition['category']]->id,
                    'author_id' => $author?->id,
                    'excerpt' => $definition['excerpt'],
                    'content' => $definition['content'],
                    'published_at' => $definition['days'] === null ? null : now()->subDays($definition['days']),
                ]
            );
        }
    }
}
