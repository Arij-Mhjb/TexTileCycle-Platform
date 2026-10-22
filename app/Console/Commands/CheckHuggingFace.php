<?php

namespace App\Console\Commands;

use App\Services\Ai\HuggingFaceClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Diagnostic : le jeton Hugging Face est-il valide, a-t-il des crédits, quels modèles répondent ? */
class CheckHuggingFace extends Command
{
    protected $signature = 'huggingface:check';

    protected $description = 'Vérifie le jeton Hugging Face, les crédits et les modèles utilisés par les modules';

    public function handle(HuggingFaceClient $client): int
    {
        if (! $client->configured()) {
            $this->warn('HF_TOKEN absent du fichier .env : les fonctions IA tournent en mode local.');

            return self::SUCCESS;
        }
        $client->reset();

        try {
            $whoami = Http::withToken((string) config('huggingface.token'))->timeout(15)->get('https://huggingface.co/api/whoami-v2');
            $this->line($whoami->successful()
                ? '✔ Jeton valide pour le compte « '.$whoami->json('name').' »'
                : '✘ Jeton refusé ('.$whoami->status().') : recréez-le sur https://huggingface.co/settings/tokens');
        } catch (Throwable $exception) {
            $this->error('Hugging Face injoignable : '.$exception->getMessage());

            return self::FAILURE;
        }

        $tests = [
            'Zero-shot texte ('.config('huggingface.zero_shot_model').')' => fn () => $client->zeroShotText('Une veste écologique.', ['écologie', 'sport']),
            'LLM avec outils ('.(config('huggingface.chat_models')[0] ?? '—').')' => fn () => $client->chat([['role' => 'user', 'content' => 'Réponds OK.']], ['max_tokens' => 5]),
        ];
        foreach ($tests as $label => $test) {
            $ok = $test() !== null;
            $status = $client->status();
            $this->line(($ok ? '✔ ' : '✘ ').$label.($ok ? '' : ' : '.$status['message']));
            if ($status['state'] !== 'ok') {
                break;
            }
        }
        $this->newLine();
        $this->line('La reconnaissance de matière par photo (CLIP) tourne dans le navigateur : elle ne consomme aucun crédit.');

        return self::SUCCESS;
    }
}
