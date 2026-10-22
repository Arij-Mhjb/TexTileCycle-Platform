<?php

namespace App\Services\Ai;

use App\Models\Material;
use App\Models\ProductImpactReport;
use App\Services\ImpactCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * IA du module Impact ESG : recommandations pour améliorer le score d'un produit.
 * Les gains sont SIMULÉS par le moteur de calcul (chiffres vérifiables) ;
 * Gemini priorise et explique les actions en langage clair.
 */
class ImpactAdvisorService
{
    /** Alternatives moins impactantes, par mot-clé de matière actuelle. */
    private const SUBSTITUTES = [
        'polyester vierge' => 'Polyester recyclé',
        'polyamide / nylon' => 'Polyamide recyclé',
        'coton conventionnel' => 'Coton recyclé',
        'coton biologique' => 'Coton recyclé',
        'laine merinos' => 'Laine recyclée',
        'cachemire' => 'Laine recyclée',
        'alpaga' => 'Laine recyclée',
        'cuir bovin' => 'Alternative cuir de cactus',
        'viscose' => 'Lyocell',
        'modal' => 'Lyocell',
        'acrylique' => 'Polyester recyclé',
    ];

    public function __construct(private GeminiJsonClient $gemini, private ImpactCalculationService $impact) {}

    /**
     * @return array{summary: string, current_score: int, best_score: int, recommendations: array<int, array<string, mixed>>, source: string, generated_at: string}
     */
    public function recommend(ProductImpactReport $report): array
    {
        $product = $report->product()->with(['materials', 'returns'])->firstOrFail();
        $weight = (float) $report->product_weight_kg;
        $current = $this->impact->compute($product, $weight)['score'];
        $candidates = [];

        // 1. Substitution de matières, simulée une par une.
        foreach ($product->materials as $material) {
            $target = $this->substituteFor($material);
            if (! $target) {
                continue;
            }
            $simulated = $this->swap($product->materials, $material, $target);
            $score = $this->impact->compute($product, $weight, $simulated)['score'];
            if ($score > $current) {
                $candidates[] = [
                    'id' => 'swap-'.$material->id,
                    'type' => 'material',
                    'title' => 'Remplacer « '.$material->name.' » par « '.$target->name.' »',
                    'detail' => ($material->pivot->percentage ? (float) $material->pivot->percentage.' % de la composition' : 'Part non renseignée').' : la matière alternative réduit le CO₂, l’eau et l’énergie de l’étape matières.',
                    'projected_score' => $score,
                    'gain' => $score - $current,
                    'swap' => [$material->id, $target->id],
                ];
            }
        }

        // 2. Leviers de circularité.
        foreach (['repairable' => 'Rendre le produit réparable (pièces détachées, coutures accessibles)', 'recyclable' => 'Concevoir pour le recyclage (mono-matière, sans mélange inséparable)', 'reusable' => 'Organiser la réutilisation (reprise, seconde main)'] as $flag => $title) {
            if (! $product->{$flag}) {
                $score = $this->impact->compute($product, $weight, null, [$flag => true])['score'];
                if ($score > $current) {
                    $candidates[] = ['id' => 'flag-'.$flag, 'type' => 'circularity', 'title' => $title, 'detail' => 'Augmente le bonus de circularité et réduit les déchets de fin de vie.', 'projected_score' => $score, 'gain' => $score - $current];
                }
            }
        }

        $candidates = collect($candidates)->sortByDesc('gain')->take(5)->values();

        // 3. Scénario combiné : toutes les recommandations appliquées.
        $allMaterials = $product->materials;
        foreach ($candidates->where('type', 'material') as $candidate) {
            [$fromId, $toId] = $candidate['swap'];
            $allMaterials = $this->swap($allMaterials, $allMaterials->firstWhere('id', $fromId), Material::find($toId));
        }
        $flags = $candidates->where('type', 'circularity')->mapWithKeys(fn ($candidate) => [Str::after($candidate['id'], 'flag-') => true])->all();
        $best = $candidates->isEmpty() ? $current : $this->impact->compute($product, $weight, $allMaterials, $flags)['score'];

        $summary = $candidates->isEmpty()
            ? 'Ce produit est déjà bien optimisé : aucune substitution simple n’améliore son score.'
            : 'En appliquant ces '.$candidates->count().' action(s), le score passerait de '.$current.' à '.$best.'/100 (classe '.$this->impact->grade($best).').';
        $source = 'local';

        $answer = $candidates->isEmpty() ? null : $this->gemini->ask($this->prompt($report, $candidates, $current, $best), [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'explanations' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string'], 'explanation' => ['type' => 'string']], 'required' => ['id', 'explanation']]],
            ],
            'required' => ['summary', 'explanations'],
        ]);
        if ($answer && filled($answer['summary'] ?? null)) {
            $summary = (string) $answer['summary'];
            $explanations = collect($answer['explanations'] ?? [])->pluck('explanation', 'id');
            $candidates = $candidates->map(fn ($candidate) => isset($explanations[$candidate['id']]) ? ['detail' => (string) $explanations[$candidate['id']]] + $candidate : $candidate);
            $source = 'gemini';
        }

        $result = [
            'summary' => $summary,
            'current_score' => $current,
            'best_score' => $best,
            'recommendations' => $candidates->map(fn ($candidate) => collect($candidate)->except('swap')->all())->all(),
            'source' => $source,
            'generated_at' => now()->toIso8601String(),
        ];
        $report->update(['ai_recommendations' => $result]);

        return $result;
    }

    private function substituteFor(Material $material): ?Material
    {
        $name = Str::lower(Str::ascii($material->name));
        foreach (self::SUBSTITUTES as $keyword => $targetName) {
            if (Str::contains($name, $keyword) && $targetName !== $material->name) {
                return Material::where('name', $targetName)->first();
            }
        }

        return null;
    }

    /** Copie de la composition où $from est remplacé par $to (même pourcentage). */
    private function swap(Collection $materials, ?Material $from, ?Material $to): Collection
    {
        if (! $from || ! $to) {
            return $materials;
        }

        return $materials->map(function (Material $material) use ($from, $to) {
            if ($material->id !== $from->id) {
                return $material;
            }
            $replacement = clone $to;
            $replacement->setRelation('pivot', $material->pivot);

            return $replacement;
        });
    }

    private function prompt(ProductImpactReport $report, Collection $candidates, int $current, int $best): string
    {
        $facts = json_encode([
            'produit' => $report->product->name, 'categorie' => $report->product->category,
            'co2_kg' => (float) $report->carbon_kg, 'eau_l' => (float) $report->water_l,
            'score_actuel' => $current, 'score_si_tout_applique' => $best,
            'actions_simulees' => $candidates->map(fn ($c) => collect($c)->only(['id', 'title', 'projected_score', 'gain']))->all(),
        ], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Tu es consultant en éco-conception textile. Les actions ci-dessous ont été simulées par notre moteur de calcul ACV :
n'invente aucun chiffre et ne modifie pas les scores. Rédige en français :
- "summary" : 2 phrases qui expliquent à la marque le potentiel d'amélioration ;
- "explanations" : pour chaque action (même "id"), une explication concrète et actionnable de 1 à 2 phrases (pourquoi, comment, fournisseur ou certification à demander).
Données : {$facts}
PROMPT;
    }
}
