<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;

class GeminiVisionService
{
    private const MAX_INLINE_BYTES = 14_000_000;

    public function enabled(): bool
    {
        return (bool) config('services.gemini.enabled')
            && filled(config('services.gemini.key'));
    }

    /**
     * Analyse les images sans transformer les propositions en données vérifiées.
     *
     * @param  array<int, array{path: string, type: string}>  $images
     */
    public function suggest(array $images, array $localData = []): ?array
    {
        if (! $this->enabled() || $images === []) {
            return null;
        }

        [$imageInputs, $temporaryFiles] = $this->imageInputs($images);
        if ($imageInputs === []) {
            return null;
        }

        try {
            $response = $this->client()->post(
                'https://generativelanguage.googleapis.com/v1beta/interactions',
                [
                    'model' => config('services.gemini.model', 'gemini-3.5-flash-lite'),
                    'input' => [
                        ['type' => 'text', 'text' => $this->prompt($localData)],
                        ...$imageInputs,
                    ],
                    'response_format' => [
                        'type' => 'text',
                        'mime_type' => 'application/json',
                        'schema' => $this->schema(),
                    ],
                ]
            );

            $response->throw();
            $text = $this->responseText($response->json());
            $decoded = is_string($text) ? json_decode($text, true) : null;

            if (! is_array($decoded)) {
                throw new RuntimeException('Gemini a retourné une réponse JSON illisible.');
            }

            return $decoded;
        } finally {
            foreach ($temporaryFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout((int) config('services.gemini.timeout', 45))
            ->retry(2, 400, throw: false);
    }

    private function prompt(array $localData): string
    {
        $known = collect($localData)
            ->only([
                'brand', 'product_name', 'sku', 'barcode', 'category', 'color', 'size',
                'retailer', 'purchase_date', 'purchase_price', 'currency', 'composition',
                'manufacturing_country', 'condition', 'defects',
            ])
            ->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Tu analyses plusieurs photos du même article textile : ticket, étiquette de composition, vue du produit et éventuellement défauts.
Retourne uniquement les informations soutenues par ce qui est réellement visible. Utilise null ou [] si une valeur n'est pas lisible.
Tu peux déduire prudemment la catégorie, la couleur dominante et l'état visuel. N'invente jamais une marque, un SKU, un code-barres, une date, un prix, une taille ou un pourcentage de composition.
Le code-barres doit contenir uniquement 8, 12, 13 ou 14 chiffres. La date doit être au format YYYY-MM-DD et la devise au format ISO 4217.
Pour chaque valeur non vide, donne une confiance entière de 0 à 100 et une preuve brève en français indiquant l'image ou le texte visible.
Les données OCR locales ci-dessous sont un indice potentiellement bruité. Confirme-les visuellement quand possible et concentre-toi sur les champs manquants :
{$known}
PROMPT;
    }

    private function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];
        $nullableNumber = ['type' => ['number', 'null']];
        $fields = [
            'brand' => $nullableString,
            'product_name' => $nullableString,
            'sku' => $nullableString,
            'barcode' => $nullableString,
            'category' => $nullableString,
            'color' => $nullableString,
            'size' => $nullableString,
            'manufacturing_country' => $nullableString,
            'retailer' => $nullableString,
            'purchase_date' => ['type' => ['string', 'null'], 'format' => 'date'],
            'purchase_price' => $nullableNumber,
            'currency' => $nullableString,
            'composition' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'material' => ['type' => 'string'],
                        'percentage' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100],
                    ],
                    'required' => ['material', 'percentage'],
                    'additionalProperties' => false,
                ],
            ],
            'condition' => [
                'type' => ['string', 'null'],
                'enum' => ['good', 'wearable', 'minor_damage', 'damaged', 'unusable', 'unknown', null],
            ],
            'defects' => ['type' => 'array', 'items' => ['type' => 'string']],
        ];

        $confidenceProperties = [];
        $evidenceProperties = [];
        foreach (array_keys($fields) as $field) {
            $confidenceProperties[$field] = ['type' => ['integer', 'null'], 'minimum' => 0, 'maximum' => 100];
            $evidenceProperties[$field] = $nullableString;
        }

        return [
            'type' => 'object',
            'properties' => [
                ...$fields,
                'field_confidence' => [
                    'type' => 'object',
                    'properties' => $confidenceProperties,
                    'required' => array_keys($confidenceProperties),
                    'additionalProperties' => false,
                ],
                'field_evidence' => [
                    'type' => 'object',
                    'properties' => $evidenceProperties,
                    'required' => array_keys($evidenceProperties),
                    'additionalProperties' => false,
                ],
            ],
            'required' => [...array_keys($fields), 'field_confidence', 'field_evidence'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array{0: array<int, array<string, string>>, 1: array<int, string>}
     */
    private function imageInputs(array $images): array
    {
        $inputs = [];
        $temporaryFiles = [];
        $totalBytes = 0;

        foreach ($images as $image) {
            $path = $image['path'] ?? null;
            if (! is_string($path) || ! is_file($path)) {
                continue;
            }

            $prepared = $this->prepareImage($path);
            if ($prepared !== $path) {
                $temporaryFiles[] = $prepared;
            }

            $size = filesize($prepared) ?: 0;
            if ($size === 0 || ($totalBytes + $size) > self::MAX_INLINE_BYTES) {
                continue;
            }

            $bytes = file_get_contents($prepared);
            if ($bytes === false) {
                continue;
            }

            $mimeType = mime_content_type($prepared) ?: 'image/jpeg';
            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                continue;
            }

            $totalBytes += $size;
            $inputs[] = [
                'type' => 'image',
                'data' => base64_encode($bytes),
                'mime_type' => $mimeType,
            ];
            $inputs[] = [
                'type' => 'text',
                'text' => 'Type de l’image précédente : '.($image['type'] ?? 'inconnu'),
            ];
        }

        return [$inputs, $temporaryFiles];
    }

    private function prepareImage(string $path): string
    {
        $size = filesize($path) ?: 0;
        $dimensions = @getimagesize($path);
        $needsResize = $size > 2_000_000
            || (($dimensions[0] ?? 0) > 2000)
            || (($dimensions[1] ?? 0) > 2000);

        if (! $needsResize) {
            return $path;
        }

        $target = sys_get_temp_dir().'/textilecycle-gemini-'.bin2hex(random_bytes(8)).'.jpg';
        $process = new Process([
            'convert', $path, '-auto-orient', '-resize', '1800x1800>',
            '-strip', '-quality', '82', $target,
        ]);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful() && is_file($target)) {
            return $target;
        }

        if (is_file($target)) {
            @unlink($target);
        }

        return $path;
    }

    private function responseText(array $response): ?string
    {
        foreach (['interaction.output_text', 'output_text', 'interaction.outputText', 'outputText'] as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $texts = [];
        array_walk_recursive($response, function ($value, $key) use (&$texts) {
            if (in_array($key, ['text', 'output_text', 'outputText'], true) && is_string($value)) {
                $texts[] = $value;
            }
        });

        return collect($texts)->last(function ($value) {
            json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE;
        });
    }
}
