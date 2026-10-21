<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client Hugging Face Inference (modèles open source hébergés sur router.huggingface.co).
 * Tâche utilisée : classification d'image zero-shot (CLIP) — la photo est comparée à des libellés en texte.
 * Retourne null si le jeton manque ou si l'API ne répond pas : l'appelant utilise alors son mode local.
 */
class HuggingFaceClient
{
    private const MAX_IMAGE_BYTES = 8_000_000;

    public function enabled(): bool
    {
        return (bool) config('huggingface.enabled') && filled(config('huggingface.token'));
    }

    /**
     * Compare une image à des libellés texte (zero-shot).
     *
     * @param  array<int, string>  $labels
     * @return array<string, float>|null libellé => score (0–1), trié du plus probable au moins probable
     */
    public function classifyImage(string $imagePath, array $labels): ?array
    {
        if (! $this->enabled() || $labels === [] || ! is_file($imagePath) || filesize($imagePath) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        $response = $this->post(config('huggingface.vision_model'), [
            'inputs' => base64_encode((string) file_get_contents($imagePath)),
            'parameters' => ['candidate_labels' => array_values($labels)],
        ]);
        if (! is_array($response)) {
            return null;
        }

        $scores = [];
        foreach ($response as $row) {
            if (is_array($row) && isset($row['label'], $row['score'])) {
                $scores[(string) $row['label']] = (float) $row['score'];
            }
        }
        arsort($scores);

        return $scores === [] ? null : $scores;
    }

    private function post(string $model, array $payload): mixed
    {
        try {
            $response = Http::acceptJson()->asJson()
                ->withToken((string) config('huggingface.token'))
                ->withHeaders(['x-wait-for-model' => 'true'])
                ->timeout((int) config('huggingface.timeout', 30))
                ->retry(2, 500, throw: false)
                ->post(rtrim((string) config('huggingface.base_url'), '/').'/'.$model, $payload);

            if (! $response->successful()) {
                Log::warning('Hugging Face ('.$model.') : réponse '.$response->status().' '.mb_substr($response->body(), 0, 200));

                return null;
            }

            return $response->json();
        } catch (Throwable $exception) {
            Log::warning('Hugging Face indisponible : '.$exception->getMessage());

            return null;
        }
    }
}
