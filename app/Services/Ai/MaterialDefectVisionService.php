<?php

namespace App\Services\Ai;

/**
 * Analyse de la photo jointe à un retour fournisseur : quels défauts sont visibles sur la matière ?
 * 1. CLIP (open source, Hugging Face, zero-shot) ; 2. sinon Gemini Vision ; 3. sinon aucune analyse.
 * Le résultat aide le fournisseur et l'admin : il confirme (ou non) le motif déclaré par la marque.
 */
class MaterialDefectVisionService
{
    /** Libellé envoyé au modèle (CLIP est entraîné en anglais) => [libellé affiché, est un défaut ?]. */
    public const LABELS = [
        'a photo of fabric with a stain' => ['Tache', true],
        'a photo of fabric with a hole or a tear' => ['Trou ou déchirure', true],
        'a photo of faded discolored fabric' => ['Décoloration', true],
        'a photo of fabric with a weaving defect or pulled threads' => ['Défaut de tissage (fils tirés)', true],
        'a photo of wet or moldy fabric' => ['Humidité ou moisissure', true],
        'a photo of fabric of the wrong color' => ['Mauvais coloris', true],
        'a photo of clean fabric rolls in perfect condition' => ['Aucun défaut visible', false],
        'a photo of yarn bobbins in good condition' => ['Bobines de fil en bon état', false],
    ];

    private const THRESHOLD = 0.12;

    public function __construct(private HuggingFaceClient $huggingFace, private GeminiJsonClient $gemini) {}

    /**
     * @return array{source: string, model: ?string, findings: array<int, array{label: string, confidence: int, defect: bool}>, defect_visible: bool, consistent: ?bool, summary: string}
     */
    public function analyse(string $imagePath, string $reason): array
    {
        $scores = $this->huggingFace->classifyImage($imagePath, array_keys(self::LABELS));
        if ($scores) {
            return $this->fromScores($scores, $reason, 'huggingface', (string) config('huggingface.vision_model'));
        }

        return $this->withGemini($imagePath, $reason);
    }

    /**
     * Scores CLIP déjà calculés (par exemple dans le navigateur avec Transformers.js) : seuls les libellés connus
     * sont gardés, et la conclusion (défaut visible, cohérence avec la déclaration) est refaite ici.
     *
     * @param  array<string, float|int|string>  $scores  libellé anglais => probabilité
     */
    public function fromScores(array $scores, string $reason, string $source, string $model): array
    {
        $scores = collect($scores)->only(array_keys(self::LABELS))->map(fn ($score) => max(0.0, min(1.0, (float) $score)))->sortDesc()->all();
        if ($scores === []) {
            return $this->unavailable();
        }
        $findings = [];
        foreach ($scores as $prompt => $score) {
            if ($score >= self::THRESHOLD && count($findings) < 3 && isset(self::LABELS[$prompt])) {
                [$label, $isDefect] = self::LABELS[$prompt];
                $findings[] = ['label' => $label, 'confidence' => (int) round($score * 100), 'defect' => $isDefect];
            }
        }
        if ($findings === []) {
            $top = array_key_first($scores);
            $findings[] = ['label' => self::LABELS[$top][0], 'confidence' => (int) round($scores[$top] * 100), 'defect' => self::LABELS[$top][1]];
        }

        return $this->conclude($source, $model, $findings, $reason);
    }

    private function withGemini(string $imagePath, string $reason): array
    {
        $answer = $this->gemini->ask($this->prompt(), $this->schema(), [$imagePath]);
        if (is_array($answer) && isset($answer['defects'])) {
            $findings = collect((array) $answer['defects'])->filter(fn ($row) => is_array($row) && filled($row['label'] ?? null))->take(3)
                ->map(fn ($row) => ['label' => (string) $row['label'], 'confidence' => (int) ($row['confidence'] ?? 70), 'defect' => true])->values()->all();
            if ($findings === []) {
                $findings[] = ['label' => 'Aucun défaut visible', 'confidence' => (int) ($answer['confidence'] ?? 70), 'defect' => false];
            }

            return $this->conclude('gemini-vision', (string) config('services.gemini.model'), $findings, $reason);
        }

        return $this->unavailable();
    }

    private function unavailable(): array
    {
        return ['source' => 'none', 'model' => null, 'findings' => [], 'defect_visible' => false, 'consistent' => null, 'summary' => 'Analyse IA indisponible : la déclaration de la marque fait foi.'];
    }

    private function conclude(string $source, ?string $model, array $findings, string $reason): array
    {
        $defectVisible = collect($findings)->contains('defect', true);
        // Un défaut qualité doit se voir ; un surplus ou une erreur de livraison concerne une matière saine.
        $consistent = $reason === 'defect' ? $defectVisible : ! $defectVisible || $reason === 'wrong_item';
        $labels = collect($findings)->pluck('label')->join(', ');

        return [
            'source' => $source,
            'model' => $model,
            'findings' => $findings,
            'defect_visible' => $defectVisible,
            'consistent' => $consistent,
            'summary' => $defectVisible ? 'Défaut(s) visible(s) : '.$labels.'.' : 'Matière en bon état apparent.',
        ];
    }

    private function prompt(): string
    {
        return 'Tu es contrôleur qualité textile. Regarde cette photo de matière (tissu, rouleau, fil) renvoyée à un fournisseur. '
            .'Liste au plus 3 défauts visibles (tache, trou, décoloration, défaut de tissage, humidité, mauvais coloris), en français, avec une confiance 0-100. '
            .'Liste vide si la matière paraît saine. N’invente rien que la photo ne montre pas.';
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'defects' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['label' => ['type' => 'string'], 'confidence' => ['type' => 'integer']], 'required' => ['label']]],
                'confidence' => ['type' => 'integer'],
            ],
            'required' => ['defects'],
        ];
    }
}
