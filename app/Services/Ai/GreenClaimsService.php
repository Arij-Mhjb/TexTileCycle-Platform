<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Anti-greenwashing : chaque phrase d'un texte marketing est examinée.
 *  - Modèles Hugging Face : ClimateBERT (détection d'allégation environnementale + précision, textes en anglais),
 *    mDeBERTa zero-shot multilingue (textes en français) ;
 *  - Règles de la directive (UE) 2024/825 « EmpCo », applicable depuis le 27 septembre 2026 :
 *    allégations génériques (« écologique », « durable »…) interdites sans preuve, neutralité carbone par compensation
 *    interdite, labels sans certification interdits.
 * Sans jeton Hugging Face, l'analyse se fait avec les seules règles (mode local).
 */
class GreenClaimsService
{
    /** Allégations génériques visées par la directive (FR / EN). */
    private const GENERIC = [
        'écologique', 'eco-responsable', 'éco-responsable', 'écoresponsable', 'eco-friendly', 'ecofriendly', 'respectueux de l’environnement',
        'respectueux de l\'environnement', 'respectueuse de l’environnement', 'respecte la planète', 'durable', 'vert', 'verte', 'green', 'sustainable',
        'responsable', 'bon pour la planète', 'planet friendly', 'climate friendly', 'conscious', 'biodégradable', 'biosourcé',
    ];

    /** Allégations absolues ou fondées sur la compensation (interdites ou à très haut risque). */
    private const ABSOLUTE = [
        'neutre en carbone', 'carbone neutre', 'neutralité carbone', 'carbon neutral', 'climate neutral', 'net zero', 'zéro impact', 'zero impact',
        '100 % écologique', '100% écologique', '100 % durable', '100% durable', 'sans impact', 'compensé', 'compensée', 'compensation carbone', 'offset',
    ];

    /** Comparaisons sans point de référence. */
    private const COMPARATIVE = ['plus écologique', 'plus durable', 'moins polluant', 'moins polluante', 'plus vert', 'plus responsable', 'greener', 'more sustainable'];

    /** Éléments qui rendent une allégation vérifiable. */
    private const PROOF = ['GOTS', 'GRS', 'RWS', 'OEKO-TEX', 'OEKO TEX', 'FSC', 'Ecolabel', 'EU Ecolabel', 'Bluesign', 'Cradle to Cradle', 'Fairtrade', 'ACV', 'analyse de cycle de vie'];

    /** Sujets environnementaux (début de mot). */
    private const TOPICS = ['recycl', 'carbone', 'co2', 'co₂', 'eau', 'planète', 'environnement', 'climat', 'biologique', 'bio', 'organic', 'émission', 'déchet', 'circulaire', 'seconde vie', 'upcycl'];

    public function __construct(private HuggingFaceClient $huggingFace) {}

    /**
     * @return array{score: int, level: string, sentences: array<int, array>, counts: array<string, int>, source: string, models: array<int, string>, regulation: string}
     */
    public function analyse(string $text): array
    {
        $text = trim($text);

        return Cache::remember('green-claims:'.md5($text).':'.(int) $this->huggingFace->enabled(), now()->addDay(), function () use ($text) {
            $models = [];
            $sentences = collect($this->sentences($text))->map(function (string $sentence) use (&$models) {
                $rules = $this->rules($sentence);
                $ai = $this->model($sentence);
                if ($ai) {
                    $models = array_values(array_unique([...$models, ...$ai['models']]));
                }

                return $this->verdict($sentence, $rules, $ai);
            })->values()->all();

            $weights = ['misleading' => 100, 'vague' => 60, 'unproven' => 35, 'specific' => 0, 'none' => 0];
            $claims = collect($sentences)->where('verdict', '!=', 'none');
            $score = $claims->isEmpty() ? 0 : (int) round($claims->avg(fn ($row) => $weights[$row['verdict']]));
            if ($claims->contains('verdict', 'misleading')) {
                $score = max($score, 75);
            }

            return [
                'score' => $score,
                'level' => $score >= 60 ? 'élevé' : ($score >= 30 ? 'moyen' : 'faible'),
                'sentences' => $sentences,
                'counts' => collect($weights)->map(fn ($weight, $verdict) => collect($sentences)->where('verdict', $verdict)->count())->all(),
                'source' => $models !== [] ? 'huggingface' : 'local',
                'models' => $models,
                'regulation' => 'Directive (UE) 2024/825, applicable depuis le 27 septembre 2026',
            ];
        });
    }

    public const VERDICTS = [
        'misleading' => ['Trompeuse', 'Allégation absolue ou fondée sur la compensation : à retirer.'],
        'vague' => ['Vague', 'Allégation générique sans preuve : interdite telle quelle.'],
        'unproven' => ['À prouver', 'Allégation sur un sujet précis mais sans chiffre ni certification.'],
        'specific' => ['Précise', 'Allégation vérifiable (chiffre, certification ou référence).'],
        'none' => ['Neutre', 'Pas d’allégation environnementale.'],
    ];

    /** @return array<int, string> */
    private function sentences(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/(?<=[.!?;])\s+|\n+/u', $text) ?: []), fn ($s) => mb_strlen($s) > 3));
    }

    private function rules(string $sentence): array
    {
        $lower = mb_strtolower($sentence);
        $found = fn (array $terms) => array_values(array_filter($terms, fn ($term) => preg_match('/(?<![\p{L}])'.preg_quote(mb_strtolower($term), '/').'(?![\p{L}])/u', $lower)));

        return [
            'generic' => $found(self::GENERIC),
            'absolute' => $found(self::ABSOLUTE),
            'comparative' => $found(self::COMPARATIVE),
            'proof' => array_values(array_filter(self::PROOF, fn ($term) => Str::contains($sentence, $term, true))),
            'figure' => (bool) preg_match('/\d+([.,]\d+)?\s?(%|kg|g|l|litres?|tonnes?|t\b|co2|co₂)/iu', $sentence),
            'topic' => collect(self::TOPICS)->contains(fn ($term) => preg_match('/(?<![\p{L}])'.preg_quote($term, '/').($term === 'eau' || $term === 'bio' ? '(?![\p{L}])' : '').'/u', $lower)),
        ];
    }

    /** @return array{claim: ?float, specific: ?float, models: array<int, string>}|null */
    private function model(string $sentence): ?array
    {
        if (! $this->huggingFace->enabled()) {
            return null;
        }

        if ($this->isEnglish($sentence)) {
            $claim = $this->huggingFace->classifyText(config('huggingface.claims_model'), $sentence);
            $specific = $claim ? $this->huggingFace->classifyText(config('huggingface.specificity_model'), $sentence) : null;
            if ($claim) {
                return [
                    'claim' => $claim['yes'] ?? $claim['LABEL_1'] ?? (1 - ($claim['no'] ?? $claim['LABEL_0'] ?? 1)),
                    'specific' => $specific ? ($specific['spec'] ?? $specific['specific'] ?? null) : null,
                    'models' => array_filter([config('huggingface.claims_model'), $specific ? config('huggingface.specificity_model') : null]),
                ];
            }
        }

        $labels = ['une promesse écologique vague' => 'vague', 'une promesse écologique précise et chiffrée' => 'specific', 'sans rapport avec l’écologie' => 'none'];
        $scores = $this->huggingFace->zeroShotText($sentence, array_keys($labels), 'Cette phrase est {}.');
        if (! $scores) {
            return null;
        }
        $vague = $scores['une promesse écologique vague'] ?? 0;
        $precise = $scores['une promesse écologique précise et chiffrée'] ?? 0;

        return ['claim' => $vague + $precise, 'specific' => ($vague + $precise) > 0 ? $precise / ($vague + $precise) : null, 'models' => [config('huggingface.zero_shot_model')]];
    }

    private function verdict(string $sentence, array $rules, ?array $ai): array
    {
        $isClaim = $rules['generic'] || $rules['absolute'] || $rules['comparative'] || $rules['topic'] || ($ai && $ai['claim'] >= 0.6);
        $hasProof = $rules['proof'] !== [] || $rules['figure'];

        $verdict = match (true) {
            ! $isClaim => 'none',
            $rules['absolute'] !== [] => 'misleading',
            $hasProof => 'specific',
            $rules['generic'] !== [] || $rules['comparative'] !== [] || ($ai && $ai['specific'] !== null && $ai['specific'] < 0.4) => 'vague',
            default => 'unproven',
        };

        return [
            'text' => $sentence,
            'verdict' => $verdict,
            'label' => self::VERDICTS[$verdict][0],
            'why' => $this->why($verdict, $rules),
            'fix' => $this->fix($verdict, $rules),
            'terms' => array_values(array_unique([...$rules['absolute'], ...$rules['generic'], ...$rules['comparative']])),
            'ai' => $ai ? ['claim' => (int) round($ai['claim'] * 100), 'specific' => $ai['specific'] === null ? null : (int) round($ai['specific'] * 100)] : null,
        ];
    }

    private function why(string $verdict, array $rules): string
    {
        return match ($verdict) {
            'misleading' => '« '.implode(' », « ', $rules['absolute']).' » : la directive interdit les allégations de neutralité fondées sur la compensation et les promesses absolues.',
            'vague' => $rules['generic'] !== [] || $rules['comparative'] !== []
                ? '« '.implode(' », « ', [...$rules['generic'], ...$rules['comparative']]).' » : terme générique, interdit sans performance environnementale reconnue.'
                : 'Le modèle juge cette allégation peu précise.',
            'unproven' => 'Le sujet est environnemental mais aucun chiffre ni certification ne permet de le vérifier.',
            'specific' => 'Vérifiable : '.implode(', ', array_filter([$rules['proof'] ? implode(', ', $rules['proof']) : null, $rules['figure'] ? 'donnée chiffrée' : null])).'.',
            default => 'Phrase descriptive.',
        };
    }

    private function fix(string $verdict, array $rules): ?string
    {
        return match ($verdict) {
            'misleading' => 'Retirez la promesse absolue et donnez la réduction réelle mesurée (ex. « −32 % de CO₂e par rapport à la collection 2025, ACV interne »).',
            'vague' => 'Remplacez « '.($rules['generic'][0] ?? $rules['comparative'][0] ?? 'ce terme').' » par un fait : matière et pourcentage (« 60 % coton recyclé certifié GRS »), ou retirez-le.',
            'unproven' => 'Ajoutez la preuve : pourcentage, certification (GOTS, GRS, OEKO-TEX…) ou résultat de l’ACV.',
            default => null,
        };
    }

    private function isEnglish(string $sentence): bool
    {
        $words = str_word_count(mb_strtolower(Str::ascii($sentence)), 1);
        $en = count(array_intersect($words, ['the', 'and', 'with', 'our', 'is', 'made', 'from', 'for', 'of', 'this']));
        $fr = count(array_intersect($words, ['le', 'la', 'les', 'et', 'avec', 'notre', 'nos', 'est', 'en', 'des', 'du', 'pour', 'une', 'un']));

        return $en > $fr;
    }
}
