<?php

namespace App\Services;

use App\Models\MaterialRequest;
use App\Models\ProductReturn;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiMaterialCompatibilityService
{
    /**
     * @param  \Illuminate\Support\Collection<int, array{material?: string, percentage?: float|int|string}>  $composition
     * @return array{verdict: string, confidence: float, reason: string, details: array<string, mixed>, source: string}
     */
    public function evaluate(ProductReturn $return, MaterialRequest $request, $composition): array
    {
        $material = $composition->first(fn ($item) => $this->sameMaterial(
            (string) data_get($item, 'material'),
            $request->material_name,
        ));

        $percentage = $material ? min(100, max(0, (float) data_get($material, 'percentage', 0))) : 0.0;
        $categoryRequired = collect($request->categories)->filter()->values();
        $conditionRequired = collect($request->accepted_conditions)->filter()->values();

        $checks = [
            'material' => [
                'passed' => $material !== null,
                'actual' => data_get($material, 'material'),
                'required' => $request->material_name,
            ],
            'percentage' => [
                'passed' => $material !== null && $percentage >= (float) $request->minimum_percentage,
                'actual' => $percentage,
                'required' => (float) $request->minimum_percentage,
            ],
            'category' => [
                'passed' => $categoryRequired->isEmpty() || $categoryRequired->containsStrict($return->product->category),
                'actual' => $return->product->category,
                'required' => $categoryRequired->all(),
            ],
            'condition' => [
                'passed' => $conditionRequired->isEmpty() || $conditionRequired->containsStrict($return->condition),
                'actual' => $return->condition,
                'required' => $conditionRequired->all(),
            ],
        ];

        $passed = collect($checks)->where('passed', true)->count();
        $score = round(
            ($material ? 20 : 0)
            + (min(1, $percentage / max(1, (float) $request->minimum_percentage)) * 45)
            + ($checks['category']['passed'] ? 20 : 0)
            + ($checks['condition']['passed'] ? 15 : 0),
            2,
        );

        $allPassed = $passed === count($checks);
        $verdict = $allPassed ? 'compatible' : (($material && $score >= 55) ? 'partial' : 'incompatible');
        $reason = $this->localReason($verdict, $checks);
        $result = [
            'verdict' => $verdict,
            'confidence' => $this->confidence($return, $material !== null),
            'reason' => $reason,
            'details' => ['score' => $score, 'checks' => $checks],
            'source' => 'local',
        ];

        // Gemini ne traite que les cas ambigus. Les règles métier restent la barrière de sécurité.
        if ($verdict !== 'partial' || ! $this->geminiEnabled()) {
            return $result;
        }

        try {
            $ai = $this->askGemini($return, $request, $composition->values()->all(), $result);
            if (! is_array($ai) || ! in_array(data_get($ai, 'verdict'), ['partial', 'incompatible'], true)) {
                return $result;
            }

            return [
                ...$result,
                'verdict' => $ai['verdict'],
                'confidence' => min(100, max(0, (float) ($ai['confidence'] ?? $result['confidence']))),
                'reason' => filled($ai['reason'] ?? null) ? $ai['reason'] : $result['reason'],
                'source' => 'gemini',
            ];
        } catch (Throwable) {
            return $result;
        }
    }

    private function sameMaterial(string $actual, string $required): bool
    {
        $actual = Str::of($actual)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->value();
        $required = Str::of($required)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->value();

        return $actual !== '' && $required !== ''
            && (str_contains($actual, $required) || str_contains($required, $actual));
    }

    private function confidence(ProductReturn $return, bool $materialFound): float
    {
        $known = collect([
            $materialFound,
            filled($return->product?->category),
            filled($return->condition),
            filled($return->confirmed_weight_kg ?? $return->weight_kg),
        ])->filter()->count();

        return round(55 + ($known * 10), 2);
    }

    private function localReason(string $verdict, array $checks): string
    {
        if ($verdict === 'compatible') {
            return 'La matière, la pureté minimale, la catégorie et l’état répondent à la demande de la marque.';
        }

        $failed = collect($checks)->reject(fn ($check) => $check['passed'])->keys()->map(fn ($key) => match ($key) {
            'material' => 'matière recherchée absente',
            'percentage' => 'pourcentage inférieur au minimum',
            'category' => 'catégorie non acceptée',
            'condition' => 'état non accepté',
            default => $key,
        })->implode(', ');

        return ($verdict === 'partial' ? 'Compatibilité possible, mais contrôle nécessaire : ' : 'Non compatible : ').$failed.'.';
    }

    private function geminiEnabled(): bool
    {
        return (bool) config('services.gemini.enabled') && filled(config('services.gemini.key'));
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->asJson()
            ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout((int) config('services.gemini.timeout', 45))
            ->retry(1, 300, throw: false);
    }

    private function askGemini(ProductReturn $return, MaterialRequest $request, array $composition, array $local): ?array
    {
        $payload = [
            'retour' => [
                'composition' => $composition,
                'category' => $return->product->category,
                'condition' => $return->condition,
            ],
            'demande' => [
                'material' => $request->material_name,
                'minimum_percentage' => (float) $request->minimum_percentage,
                'categories' => $request->categories,
                'accepted_conditions' => $request->accepted_conditions,
            ],
            'analyse_locale' => $local,
        ];

        $response = $this->client()->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
            'model' => config('services.gemini.model', 'gemini-3.5-flash-lite'),
            'input' => [[
                'type' => 'text',
                'text' => "Évalue ce cas textile ambigu. Réponds en français. Tu ne peux proposer que partial ou incompatible. Données :\n".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]],
            'response_format' => [
                'type' => 'text',
                'mime_type' => 'application/json',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'verdict' => ['type' => 'string', 'enum' => ['partial', 'incompatible']],
                        'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100],
                        'reason' => ['type' => 'string'],
                    ],
                    'required' => ['verdict', 'confidence', 'reason'],
                    'additionalProperties' => false,
                ],
            ],
        ]);

        $response->throw();
        $body = $response->json();
        $text = data_get($body, 'interaction.output_text') ?? data_get($body, 'output_text');
        if (! is_string($text)) {
            $texts = [];
            array_walk_recursive($body, function ($value, $key) use (&$texts) {
                if (in_array($key, ['text', 'output_text', 'outputText'], true) && is_string($value)) {
                    $texts[] = $value;
                }
            });
            $text = collect($texts)->last(fn ($value) => is_array(json_decode($value, true)));
        }

        $decoded = is_string($text) ? json_decode($text, true) : null;

        return is_array($decoded) ? $decoded : null;
    }
}
