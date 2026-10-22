<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client Hugging Face (modèles open source hébergés sur router.huggingface.co).
 *  - classifyImage : classification d'image zero-shot (CLIP) ;
 *  - classifyText  : classification de texte (ClimateBERT…) ;
 *  - zeroShotText  : classification de texte zero-shot (mDeBERTa, BART-MNLI) ;
 *  - chat          : LLM / modèle vision-langage compatible OpenAI, avec appels d'outils.
 * Retourne null si le jeton manque ou si l'API ne répond pas : l'appelant passe alors en mode local.
 *
 * Disjoncteur : si Hugging Face répond « plus de crédits » (402) ou « jeton refusé » (401/403), on arrête
 * d'appeler l'API pendant 15 minutes ; un modèle « non servi » (400/404) est ignoré pendant 24 h.
 * Les pages restent rapides au lieu d'attendre des appels voués à l'échec.
 */
class HuggingFaceClient
{
    private const MAX_IMAGE_BYTES = 8_000_000;

    private const DOWN_KEY = 'huggingface:down';

    public function configured(): bool
    {
        return (bool) config('huggingface.enabled') && filled(config('huggingface.token'));
    }

    public function enabled(): bool
    {
        return $this->configured() && ! Cache::has(self::DOWN_KEY);
    }

    /** @return array{state: string, message: string} ok | off | no_credits | bad_token */
    public function status(): array
    {
        if (! $this->configured()) {
            return ['state' => 'off', 'message' => 'Pas de jeton HF_TOKEN : les fonctions IA passent en mode local.'];
        }

        return Cache::get(self::DOWN_KEY) ?? ['state' => 'ok', 'message' => 'Hugging Face disponible.'];
    }

    /** Réinitialise le disjoncteur (commande huggingface:check). */
    public function reset(): void
    {
        Cache::forget(self::DOWN_KEY);
    }

    /**
     * Compare une image à des libellés texte (zero-shot).
     *
     * @param  array<int, string>  $labels
     * @return array<string, float>|null libellé => score (0–1), trié du plus probable au moins probable
     */
    public function classifyImage(string $imagePath, array $labels): ?array
    {
        if (! $this->enabled() || $labels === [] || ! $this->readableImage($imagePath)) {
            return null;
        }

        return $this->scores($this->post(config('huggingface.vision_model'), [
            'inputs' => base64_encode((string) file_get_contents($imagePath)),
            'parameters' => ['candidate_labels' => array_values($labels)],
        ]));
    }

    /** @return array<string, float>|null libellé => score, pour un modèle de classification de texte */
    public function classifyText(string $model, string $text): ?array
    {
        if (! $this->enabled() || trim($text) === '') {
            return null;
        }

        return $this->scores($this->post($model, ['inputs' => $text]));
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<string, float>|null
     */
    public function zeroShotText(string $text, array $labels, ?string $template = null, ?string $model = null): ?array
    {
        if (! $this->enabled() || trim($text) === '' || $labels === []) {
            return null;
        }

        return $this->scores($this->post($model ?? config('huggingface.zero_shot_model'), [
            'inputs' => $text,
            'parameters' => array_filter(['candidate_labels' => array_values($labels), 'hypothesis_template' => $template]),
        ]));
    }

    /**
     * Appel « chat completions » (format OpenAI) sur le routeur Hugging Face. Les modèles sont essayés dans l'ordre :
     * si un fournisseur ne sert pas le premier, on passe au suivant.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options  tools, tool_choice, max_tokens, temperature…
     * @param  array<int, string>|null  $models
     * @return array{message: array<string, mixed>, model: string}|null
     */
    public function chat(array $messages, array $options = [], ?array $models = null): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        // Budget total : on n'enchaîne pas trois modèles lents au point de dépasser la limite de PHP.
        $deadline = microtime(true) + (int) config('huggingface.chat_budget', 45);
        self::extendTimeLimit();

        foreach ($models ?? config('huggingface.chat_models', []) as $model) {
            if (! $this->enabled()) {
                return null;
            }
            if (! $this->supported($model)) {
                continue;
            }
            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining < 5) {
                Log::warning('Hugging Face chat : budget de temps épuisé, passage au plan B.');

                return null;
            }
            try {
                $response = Http::acceptJson()->asJson()
                    ->withToken((string) config('huggingface.token'))
                    ->connectTimeout(10)
                    ->timeout(min((int) config('huggingface.chat_timeout', 60), $remaining))
                    ->post((string) config('huggingface.chat_url'), ['model' => $model, 'messages' => $messages] + $options + ['max_tokens' => 900, 'temperature' => 0.2]);
            } catch (Throwable $exception) {
                Log::warning('Hugging Face chat ('.$model.') indisponible : '.$exception->getMessage());

                continue;
            }
            $message = $response->json('choices.0.message');
            if ($response->successful() && is_array($message)) {
                return ['message' => $message, 'model' => $model];
            }
            $this->failure($model, $response);
        }

        return null;
    }

    /**
     * Demande une réponse JSON à un modèle vision-langage, à partir d'une consigne et (optionnellement) d'une image.
     *
     * @return array{data: array<string, mixed>, model: string}|null
     */
    public function visionJson(string $prompt, ?string $imagePath = null): ?array
    {
        $content = [['type' => 'text', 'text' => $prompt."\nRéponds uniquement avec un objet JSON valide, sans texte autour."]];
        if ($imagePath !== null) {
            if (! $this->readableImage($imagePath)) {
                return null;
            }
            $mime = mime_content_type($imagePath) ?: 'image/jpeg';
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($imagePath))]];
        }

        $answer = $this->chat([['role' => 'user', 'content' => $content]], ['max_tokens' => 700, 'temperature' => 0], config('huggingface.vlm_models', []));
        $data = $answer ? self::decodeJson((string) ($answer['message']['content'] ?? '')) : null;

        return $data === null ? null : ['data' => $data, 'model' => $answer['model']];
    }

    /**
     * Un appel à un modèle distant peut durer plus que les 30 s par défaut de PHP (max_execution_time) :
     * on laisse 2 minutes à la requête, puis le plan B (Gemini, OCR, règles) prend le relais.
     */
    private static function extendTimeLimit(): void
    {
        if (function_exists('set_time_limit') && ! app()->runningUnitTests()) {
            @set_time_limit(120);
        }
    }

    /** Extrait le premier objet JSON d'une réponse de modèle (avec ou sans bloc ```json). */
    public static function decodeJson(string $text): ?array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }
        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }

    private function readableImage(string $path): bool
    {
        return is_file($path) && filesize($path) <= self::MAX_IMAGE_BYTES;
    }

    /** Réponses de classification : [{label, score}], [[{label, score}]] ou {labels: [], scores: []}. */
    private function scores(mixed $response): ?array
    {
        if (! is_array($response)) {
            return null;
        }
        if (isset($response['labels'], $response['scores']) && is_array($response['labels'])) {
            $response = array_map(fn ($label, $score) => ['label' => $label, 'score' => $score], $response['labels'], $response['scores']);
        }
        if (isset($response[0]) && is_array($response[0]) && isset($response[0][0]) && is_array($response[0][0])) {
            $response = $response[0];
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
        if (! $this->supported($model)) {
            return null;
        }
        self::extendTimeLimit();
        try {
            $response = Http::acceptJson()->asJson()
                ->withToken((string) config('huggingface.token'))
                ->withHeaders(['x-wait-for-model' => 'true'])
                ->timeout((int) config('huggingface.timeout', 30))
                ->retry(2, 500, fn ($exception) => $exception instanceof ConnectionException || ($exception instanceof RequestException && $exception->response->serverError()), throw: false)
                ->post(rtrim((string) config('huggingface.base_url'), '/').'/'.$model, $payload);

            if (! $response->successful()) {
                $this->failure($model, $response);

                return null;
            }

            return $response->json();
        } catch (Throwable $exception) {
            Log::warning('Hugging Face indisponible : '.$exception->getMessage());

            return null;
        }
    }

    private function supported(string $model): bool
    {
        return ! Cache::has('huggingface:unsupported:'.md5($model));
    }

    /** Mémorise les échecs qui se répéteraient : crédits épuisés, jeton refusé, modèle non servi. */
    private function failure(string $model, Response $response): void
    {
        $status = $response->status();
        Log::warning('Hugging Face ('.$model.') : réponse '.$status.' '.mb_substr($response->body(), 0, 200));

        if ($status === 402) {
            Cache::put(self::DOWN_KEY, ['state' => 'no_credits', 'message' => 'Crédits Hugging Face épuisés (402) : rechargez le compte ou passez à HF PRO. En attendant, mode local.'], now()->addMinutes(15));
        } elseif (in_array($status, [401, 403], true)) {
            Cache::put(self::DOWN_KEY, ['state' => 'bad_token', 'message' => 'Jeton Hugging Face refusé ('.$status.') : vérifiez HF_TOKEN et la permission « Make calls to Inference Providers ».'], now()->addMinutes(15));
        } elseif ($status === 404 || ($status === 400 && str_contains(mb_strtolower($response->body()), 'not supported'))) {
            Cache::put('huggingface:unsupported:'.md5($model), true, now()->addDay());
        }
    }
}
