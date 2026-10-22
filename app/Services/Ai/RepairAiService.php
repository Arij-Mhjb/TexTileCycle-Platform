<?php

namespace App\Services\Ai;

use App\Models\RepairOrder;
use Illuminate\Support\Str;

/**
 * IA du module Seconde vie : diagnostic d'un article abîmé (photo + description).
 * Gemini Vision analyse la photo ; sinon une analyse locale par mots-clés prend le relais.
 * Propose : réparer / revendre / recycler, grade, coût estimé et prix de revente.
 */
class RepairAiService
{
    /** Défauts reconnus localement : mot-clé => [libellé, coût TND, gravité 1-3]. */
    private const DAMAGES = [
        'fermeture' => ['Fermeture éclair défectueuse', 12, 1], 'zip' => ['Fermeture éclair défectueuse', 12, 1], 'sosta' => ['Fermeture éclair défectueuse', 12, 1],
        'bouton' => ['Bouton manquant', 4, 1], 'couture' => ['Couture décousue', 8, 1], 'ourlet' => ['Ourlet défait', 6, 1],
        'trou' => ['Trou dans le tissu', 15, 2], 'dechir' => ['Déchirure', 18, 2], 'accroc' => ['Accroc', 10, 1],
        'tache' => ['Tache', 9, 1], 'decolor' => ['Décoloration', 14, 2], 'boulochage' => ['Boulochage', 7, 1], 'bouloch' => ['Boulochage', 7, 1],
        'semelle' => ['Semelle usée', 25, 2], 'talon' => ['Talon usé', 18, 2], 'cuir' => ['Cuir abîmé', 22, 2],
        'doublure' => ['Doublure abîmée', 16, 2], 'brule' => ['Brûlure', 30, 3], 'moisi' => ['Moisissure', 30, 3], 'deforme' => ['Article déformé', 25, 3],
    ];

    /** Prix de base neuf estimé (TND) par catégorie. */
    private const BASE_PRICES = ['veste' => 220, 'manteau' => 320, 'chaussure' => 180, 'pantalon' => 120, 'jean' => 130, 'robe' => 150, 'chemise' => 90, 'pull' => 110, 'sac' => 160, 't-shirt' => 45];

    public function __construct(private GeminiJsonClient $gemini) {}

    /**
     * @return array{decision: string, grade: string, damages: array<int, string>, estimated_cost: float, resale_price: float, explanation: string, confidence: int, source: string, analysed_at: string}
     */
    public function diagnose(RepairOrder $order, ?string $photoPath = null): array
    {
        $order->loadMissing(['product.materials', 'productReturn']);
        $local = $this->localDiagnosis($order);
        $result = $local;

        $answer = $this->gemini->ask($this->prompt($order, $local), $this->schema(), $photoPath ? [$photoPath] : []);
        if ($answer && in_array($answer['decision'] ?? null, ['repair', 'resale', 'recycle'], true)) {
            $result = [
                'decision' => $answer['decision'],
                'grade' => in_array($answer['grade'] ?? null, ['A', 'B', 'C'], true) ? $answer['grade'] : $local['grade'],
                'damages' => array_values(array_filter((array) ($answer['damages'] ?? []), 'is_string')) ?: $local['damages'],
                'estimated_cost' => round(max(0, (float) ($answer['estimated_cost'] ?? $local['estimated_cost'])), 2),
                'resale_price' => round(max(0, (float) ($answer['resale_price'] ?? $local['resale_price'])), 2),
                'explanation' => (string) ($answer['explanation'] ?? $local['explanation']),
                'confidence' => (int) ($answer['confidence'] ?? 70),
                'source' => $photoPath ? 'gemini-vision' : 'gemini',
            ];
        }

        $result['analysed_at'] = now()->toIso8601String();
        $order->update(['ai_diagnosis' => $result]);

        return $result;
    }

    /** Analyse locale : mots-clés de la description + état du retour + catégorie du produit. */
    private function localDiagnosis(RepairOrder $order): array
    {
        $text = Str::lower(Str::ascii($order->issue_description.' '.$order->productReturn?->condition));
        $damages = [];
        $cost = 0;
        $severity = 0;
        foreach (self::DAMAGES as $keyword => [$label, $price, $level]) {
            if (Str::contains($text, $keyword) && ! in_array($label, $damages, true)) {
                $damages[] = $label;
                $cost += $price;
                $severity = max($severity, $level);
            }
        }
        if (Str::contains($text, ['unusable', 'inutilisable', 'damaged'])) {
            $severity = max($severity, 3);
        }

        $decision = $severity >= 3 ? 'recycle' : ($order->productReturn ? 'resale' : 'repair');
        $grade = $severity <= 1 ? ($damages === [] ? 'A' : 'B') : 'C';
        $base = $this->basePrice((string) $order->product?->category);
        $resale = round($base * ['A' => 0.55, 'B' => 0.4, 'C' => 0.25][$grade]);

        return [
            'decision' => $decision,
            'grade' => $grade,
            'damages' => $damages ?: ['Aucun défaut précis détecté dans la description'],
            'estimated_cost' => (float) max(5, $cost ?: 8),
            'resale_price' => (float) $resale,
            'explanation' => match ($decision) {
                'recycle' => 'Défaut trop important pour une réparation rentable : orientation vers le recyclage matière.',
                'resale' => 'Défauts réparables à faible coût : l’article peut être reconditionné puis revendu (≈ '.(int) $resale.' TND).',
                default => 'Réparation simple recommandée avant de rendre l’article au consommateur.',
            },
            'confidence' => $damages === [] ? 40 : 60,
            'source' => 'local',
        ];
    }

    private function basePrice(string $category): float
    {
        $category = Str::lower(Str::ascii($category));
        foreach (self::BASE_PRICES as $keyword => $price) {
            if (Str::contains($category, Str::ascii($keyword))) {
                return $price;
            }
        }

        return 100;
    }

    private function prompt(RepairOrder $order, array $local): string
    {
        $context = json_encode([
            'produit' => $order->product?->name, 'categorie' => $order->product?->category,
            'composition' => $order->product?->materials->map(fn ($m) => $m->name.' '.($m->pivot->percentage ?? '?').'%')->all(),
            'description_probleme' => $order->issue_description,
            'etat_declare' => $order->productReturn?->condition,
            'estimation_locale' => collect($local)->only(['decision', 'grade', 'estimated_cost', 'resale_price']),
        ], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Tu es expert en réparation et reconditionnement textile en Tunisie. Analyse la photo (si fournie) et la description de l'article.
Décide : "repair" (réparer et rendre au propriétaire), "resale" (reconditionner et revendre sur le marketplace) ou "recycle" (irréparable).
Donne : la liste des défauts visibles (en français), un grade après reconditionnement (A comme neuf, B très bon, C bon),
un coût de réparation estimé en TND, un prix de revente réaliste en TND en seconde main, une explication courte et une confiance 0-100.
Ne décris que ce qui est visible ou écrit ; si la photo est absente, base-toi sur la description.
Contexte : {$context}
PROMPT;
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'decision' => ['type' => 'string', 'enum' => ['repair', 'resale', 'recycle']],
                'grade' => ['type' => 'string', 'enum' => ['A', 'B', 'C']],
                'damages' => ['type' => 'array', 'items' => ['type' => 'string']],
                'estimated_cost' => ['type' => 'number', 'minimum' => 0],
                'resale_price' => ['type' => 'number', 'minimum' => 0],
                'explanation' => ['type' => 'string'],
                'confidence' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            ],
            'required' => ['decision', 'grade', 'damages', 'estimated_cost', 'resale_price', 'explanation', 'confidence'],
        ];
    }
}
