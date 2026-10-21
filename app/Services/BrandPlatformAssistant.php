<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Throwable;

class BrandPlatformAssistant
{
    public function answer(Brand $brand, string $question, string $page, array $history = []): array
    {
        $links = $this->suggestedLinks($question);
        if (! config('brand_assistant.key')) {
            return ['answer' => $this->fallback($question), 'links' => $links, 'mode' => 'guide-local'];
        }

        try {
            $knowledge = is_file(config('brand_assistant.knowledge_path'))
                ? file_get_contents(config('brand_assistant.knowledge_path')) : '';
            $messages = [['role' => 'system', 'content' => $knowledge."\n\nContexte de la marque : ".json_encode([
                'name' => $brand->name, 'products' => $brand->products()->count(),
                'active_roadmaps' => $brand->roadmaps()->where('status', 'active')->count(),
                'current_page' => $page,
            ], JSON_UNESCAPED_UNICODE)]];
            foreach (array_slice($history, -6) as $message) {
                if (in_array($message['role'] ?? null, ['user', 'assistant'], true) && is_string($message['content'] ?? null)) {
                    $messages[] = ['role' => $message['role'], 'content' => mb_substr($message['content'], 0, 1500)];
                }
            }
            $messages[] = ['role' => 'user', 'content' => $question];
            $response = Http::withToken(config('brand_assistant.key'))->acceptJson()->connectTimeout(10)->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('brand_assistant.model'), 'messages' => $messages,
                    'temperature' => 0.2, 'max_tokens' => 500,
                ])->throw();
            $answer = trim((string) $response->json('choices.0.message.content'));
            if ($answer === '') {
                throw new \RuntimeException('Réponse vide.');
            }

            return ['answer' => $answer, 'links' => $links, 'mode' => 'llm', 'model' => config('brand_assistant.model')];
        } catch (Throwable $exception) {
            report($exception);

            return ['answer' => $this->fallback($question).' Le service IA est momentanément indisponible.', 'links' => $links, 'mode' => 'guide-local'];
        }
    }

    private function suggestedLinks(string $question): array
    {
        $q = mb_strtolower($question);
        $definitions = [
            [['produit', 'sku', 'catalogue'], 'Mes produits', 'brand.products.index'],
            [['label', 'éligibilité', 'eligibilite', 'score', 'budget'], 'Analyse produit-label', 'brand.product-label-recommendations.index'],
            [['roadmap', 'feuille', 'étape', 'preuve'], 'Mes roadmaps', 'brand.roadmaps.index'],
            [['matière', 'matiere', 'recycl', 'retour'], 'Matières recherchées', 'brand.material-requests.index'],
            [['fournisseur', 'lot', 'certificat', 'traçabilité'], 'Fournisseurs', 'brand.suppliers.index'],
            [['impact', 'carbone', 'eau', 'esg'], 'Impact ESG', 'brand.impact.index'],
            [['profil', 'marque', 'logo'], 'Profil de la marque', 'brand.profile.edit'],
        ];
        $links = [];
        foreach ($definitions as [$words, $label, $route]) {
            if (collect($words)->contains(fn ($word) => str_contains($q, $word))) {
                $links[] = ['label' => $label, 'url' => route($route)];
            }
        }

        return array_slice($links ?: [['label' => 'Centre de pilotage', 'url' => route('brand.dashboard')]], 0, 3);
    }

    private function fallback(string $question): string
    {
        $q = mb_strtolower($question);
        return match (true) {
            str_contains($q, 'produit') => 'Ouvrez Produits, puis Catalogue. Créez ou complétez le produit, vérifiez que la composition totalise 100 %, puis soumettez-le. L’analyse des labels devient disponible après publication par l’administrateur.',
            str_contains($q, 'label'), str_contains($q, 'score') => 'Ouvrez Produits puis IA produit-label. Actualisez l’analyse pour afficher les labels, le score détaillé, les écarts et le budget indicatif. Choisissez ensuite un label pour créer la roadmap.',
            str_contains($q, 'roadmap'), str_contains($q, 'preuve') => 'Ouvrez Labels puis Feuilles de route. Sélectionnez une roadmap, ouvrez l’étape active, décrivez les actions et résultats, puis joignez les preuves avant soumission à l’administrateur.',
            str_contains($q, 'fournisseur'), str_contains($q, 'certificat') => 'Ouvrez Fournisseurs pour enregistrer le partenaire, suivre ses commandes et contrôler ses lots. Reliez ensuite un lot vérifié au produit dans Traçabilité.',
            str_contains($q, 'matière'), str_contains($q, 'recycl') => 'Ouvrez Circularité puis Matières recherchées. Déclarez la matière, le pourcentage minimal et la quantité. Les retours compatibles apparaîtront dans la section correspondante.',
            default => 'Je peux vous guider pour créer un produit, analyser les labels, construire une roadmap, déposer des preuves, gérer les fournisseurs, tracer les matières ou calculer l’impact. Décrivez l’objectif que vous voulez atteindre.',
        };
    }
}
