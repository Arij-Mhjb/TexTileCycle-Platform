<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client Gemini partagé par les modules Fournisseurs, Impact ESG et Seconde vie.
 * Même API et même configuration que le reste du projet (config/services.php → gemini).
 * Retourne null si l'IA est désactivée ou indisponible : chaque service a alors un mode local.
 */
class GeminiJsonClient
{
    private const MAX_IMAGE_BYTES = 8_000_000;

    public function enabled(): bool
    {
        return (bool) config('services.gemini.enabled') && filled(config('services.gemini.key'));
    }

    /**
     * @param  array<string, mixed>  $schema  JSON Schema attendu en réponse
     * @param  array<int, string>  $imagePaths  images à analyser (jpg, png, webp)
     */
    public function ask(string $prompt, array $schema, array $imagePaths = []): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $input = [['type' => 'text', 'text' => $prompt]];
            foreach ($imagePaths as $path) {
                $image = $this->image($path);
                if ($image) {
                    $input[] = $image;
                }
            }

            $response = Http::acceptJson()->asJson()
                ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                ->timeout((int) config('services.gemini.timeout', 45))
                ->retry(2, 400, throw: false)
                ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                    'model' => config('services.gemini.model', 'gemini-3.5-flash-lite'),
                    'input' => $input,
                    'response_format' => ['type' => 'text', 'mime_type' => 'application/json', 'schema' => $schema],
                ]);

            if (! $response->successful()) {
                Log::warning('Gemini (modules) : réponse '.$response->status());

                return null;
            }

            $decoded = json_decode((string) $this->text($response->json() ?? []), true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $exception) {
            Log::warning('Gemini (modules) indisponible : '.$exception->getMessage());

            return null;
        }
    }

    /** @return array<string, string>|null */
    private function image(string $path): ?array
    {
        if (! is_file($path) || filesize($path) > self::MAX_IMAGE_BYTES) {
            return null;
        }
        $mime = mime_content_type($path) ?: '';
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'], true)) {
            return ['type' => 'image', 'data' => base64_encode((string) file_get_contents($path)), 'mime_type' => $mime];
        }

        // Autres formats (GIF, BMP…) : conversion en PNG pour que Gemini puisse les lire.
        $image = function_exists('imagecreatefromstring') ? @imagecreatefromstring((string) file_get_contents($path)) : false;
        if (! $image) {
            return null;
        }
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return ['type' => 'image', 'data' => base64_encode($png), 'mime_type' => 'image/png'];
    }

    private function text(array $response): ?string
    {
        foreach (['interaction.output_text', 'output_text', 'interaction.outputText', 'outputText'] as $key) {
            $value = data_get($response, $key);
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
