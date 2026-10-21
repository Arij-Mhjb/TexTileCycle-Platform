<?php

namespace App\Services\Ai\Copilot;

use App\Models\Brand;
use App\Services\Ai\HuggingFaceClient;
use Illuminate\Support\Str;

/**
 * Copilote IA de la marque : un LLM open source (Hugging Face, appels d'outils) répond aux questions
 * sur les fournisseurs, les certificats, l'impact carbone et le recyclage en interrogeant les vraies données.
 * Sans jeton (ou si le modèle ne répond pas), un routeur local choisit les mêmes outils et rédige la réponse.
 */
class CopilotService
{
    private const MAX_ROUNDS = 4;

    public function __construct(private HuggingFaceClient $huggingFace, private CopilotTools $tools) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{answer: string, tools: array<int, array{name: string, label: string, args: array}>, source: string, model: ?string}
     */
    public function ask(Brand $brand, string $question, array $history = []): array
    {
        return $this->withModel($brand, $question, $history) ?? $this->local($brand, $question);
    }

    private function withModel(Brand $brand, string $question, array $history): ?array
    {
        if (! $this->huggingFace->enabled()) {
            return null;
        }

        $messages = [['role' => 'system', 'content' => $this->systemPrompt($brand)]];
        foreach (array_slice($history, -6) as $turn) {
            if (in_array($turn['role'] ?? null, ['user', 'assistant'], true) && filled($turn['content'] ?? null)) {
                $messages[] = ['role' => $turn['role'], 'content' => Str::limit((string) $turn['content'], 1500)];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        $used = [];
        $model = null;
        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            $reply = $this->huggingFace->chat($messages, ['tools' => $this->tools->definitions(), 'tool_choice' => 'auto'], $model ? [$model] : null);
            if (! $reply) {
                return null;
            }
            $model = $reply['model'];
            $message = $reply['message'];
            $calls = $message['tool_calls'] ?? [];
            if ($calls === []) {
                $answer = trim((string) ($message['content'] ?? ''));

                return $answer === '' ? null : ['answer' => $answer, 'tools' => $used, 'source' => 'huggingface', 'model' => $model];
            }

            $messages[] = ['role' => 'assistant', 'content' => (string) ($message['content'] ?? ''), 'tool_calls' => $calls];
            foreach ($calls as $call) {
                $name = (string) ($call['function']['name'] ?? '');
                $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?: [];
                $result = $this->tools->run($name, $args, $brand);
                $used[] = ['name' => $name, 'label' => CopilotTools::LABELS[$name] ?? $name, 'args' => $args];
                $messages[] = ['role' => 'tool', 'tool_call_id' => (string) ($call['id'] ?? $name), 'name' => $name, 'content' => json_encode($result, JSON_UNESCAPED_UNICODE)];
            }
        }

        return null;
    }

    private function systemPrompt(Brand $brand): string
    {
        return 'Tu es le copilote IA de TexTileCycle pour la marque « '.$brand->name.' ». Nous sommes le '.now()->format('d/m/Y').'. '
            .'Réponds en français, de façon courte et concrète (5 à 8 lignes, listes à puces si utile, **gras** pour les chiffres clés). '
            .'Utilise toujours les outils pour obtenir les chiffres : n’invente jamais un fournisseur, un chiffre ou une date. '
            .'Périmètre : fournisseurs et risques, certificats, commandes, impact carbone et eau des produits, objectifs carbone, recyclage des rebuts, greenwashing. '
            .'Hors de ce périmètre, dis-le poliment. Termine par une action recommandée quand c’est pertinent.';
    }

    /** Mode local : les mêmes outils, choisis par mots-clés, et une réponse rédigée à partir de leurs résultats. */
    private function local(Brand $brand, string $question): array
    {
        $q = Str::lower(Str::ascii($question));
        $quoted = preg_match('/[«"“](.+?)[»"”]/u', $question, $match) ? $match[1] : (Str::contains($question, ':') ? trim(Str::after($question, ':')) : null);
        $calls = [];
        if (Str::contains($q, ['greenwashing', 'allegation', 'slogan', 'marketing', 'phrase', 'ecologique ?', 'texte']) && filled($quoted)) {
            $calls['verifier_allegation'] = ['texte' => $quoted];
        }
        if (Str::contains($q, ['certificat', 'expir', 'gots', 'grs', 'rws'])) {
            $calls['certificats_expirants'] = ['jours' => 90];
        }
        $named = $this->tools->run('fournisseurs_risque', [], $brand)['fournisseurs'] ?? [];
        foreach ($named as $row) {
            if (Str::contains($q, Str::lower(Str::ascii(Str::before($row['nom'], ' '))))) {
                $calls['performance_fournisseur'] = ['nom' => Str::before($row['nom'], ' ')];
                break;
            }
        }
        if (! isset($calls['performance_fournisseur']) && Str::contains($q, ['risque', 'fiable', 'fournisseur', 'otif'])) {
            $calls['fournisseurs_risque'] = [];
        }
        if (Str::contains($q, ['commande', 'retard', 'livraison'])) {
            $calls['commandes_en_cours'] = [];
        }
        $checkingText = isset($calls['verifier_allegation']);
        if (! $checkingText && Str::contains($q, ['objectif', 'budget', 'carbone', 'co2', 'emission'])) {
            $calls['objectifs_carbone'] = [];
        }
        if (! $checkingText && Str::contains($q, ['impact', 'produit', 'eau', 'score', 'classe'])) {
            $calls['impact_produits'] = [];
        }
        if (Str::contains($q, ['recycl', 'rebut', 'avoir', 'frais', 'chute', 'surplus'])) {
            $calls['recyclage_bilan'] = [];
        }
        if ($calls === []) {
            $calls = ['fournisseurs_risque' => [], 'objectifs_carbone' => [], 'recyclage_bilan' => []];
        }

        $parts = [];
        $used = [];
        foreach ($calls as $name => $args) {
            $result = $this->tools->run($name, $args, $brand);
            $used[] = ['name' => $name, 'label' => CopilotTools::LABELS[$name], 'args' => $args];
            $parts[] = $this->summarise($name, $result);
        }

        return ['answer' => implode("\n\n", array_filter($parts)), 'tools' => $used, 'source' => 'local', 'model' => null];
    }

    private function summarise(string $name, array $r): string
    {
        $kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';

        return match ($name) {
            'fournisseurs_risque' => "**Risque fournisseurs**\n".collect($r['fournisseurs'] ?? [])->map(fn ($s) => '- **'.$s['nom'].'** : risque '.$s['niveau'].' (**'.$s['score_risque'].'/100**)'
                .($s['raisons'] ? ' — '.Str::lower(Str::before($s['raisons'][0], ' :')).' : '.Str::after($s['raisons'][0], ': ') : ''))->join("\n")
                .(($first = collect($r['fournisseurs'] ?? [])->first()) && $first['score_risque'] >= 30 ? "\n→ Priorité : ".$first['nom'].'. '.$first['action'] : ''),
            'certificats_expirants' => ($r['certificats'] ?? []) === [] ? '**Certificats** : aucun certificat n’expire dans les '.$r['horizon_jours'].' prochains jours.'
                : "**Certificats à renouveler**\n".collect($r['certificats'])->map(fn ($c) => '- '.$c['certificat'].' — '.$c['fournisseur'].' (lot '.$c['lot'].') : '
                    .($c['jours_restants'] < 0 ? '**expiré** depuis '.abs($c['jours_restants']).' j' : 'expire le **'.$c['expire_le'].'** (dans '.$c['jours_restants'].' j)'))->join("\n"),
            'performance_fournisseur' => isset($r['erreur']) ? $r['erreur'] : '**'.$r['nom'].'** : risque '.$r['niveau_risque'].' (**'.$r['score_risque'].'/100**), OTIF '
                .($r['otif_pourcent'] === null ? 'non mesuré' : '**'.round($r['otif_pourcent']).' %**').".\n".collect($r['facteurs'])->map(fn ($f) => '- '.$f)->join("\n")."\n→ ".($r['actions'][0] ?? ''),
            'commandes_en_cours' => ($r['commandes'] ?? []) === [] ? '**Commandes** : aucune commande en cours.' : "**Commandes en cours**\n".collect($r['commandes'])->map(fn ($o) => '- '.$o['reference'].' ('.$o['fournisseur'].') : '
                .Str::lower($o['statut']).($o['expedie_pourcent'] !== null ? ', '.$o['expedie_pourcent'].' % expédié' : '').($o['en_retard'] ? ' — **en retard**' : ''))->join("\n"),
            'impact_produits' => "**Impact des produits** (par unité)\n".collect($r['produits'] ?? [])->map(fn ($p) => '- '.$p['produit'].' : **'.number_format($p['co2e_kg_par_unite'], 1, ',', ' ').' kg CO₂e**, '
                .number_format($p['eau_litres_par_unite'], 0, ',', ' ').' L d’eau, classe '.$p['classe'])->join("\n"),
            'objectifs_carbone' => ($r['objectifs'] ?? []) === [] ? '**Objectifs carbone** : aucun objectif défini. Créez-en un dans Impact ESG → Objectifs carbone.'
                : "**Objectifs carbone**\n".collect($r['objectifs'])->map(fn ($t) => '- '.$t['objectif'].' : '.$kg($t['emissions_prevues_kg']).' CO₂e'
                    .($t['budget_kg'] ? ' pour un budget de '.$kg($t['budget_kg']).' (**'.$t['consommation_pourcent'].' %**, '.Str::lower($t['etat']).')' : '')
                    .($t['economie_possible_kg'] > 0 ? '. En passant au recyclé : **−'.$kg($t['economie_possible_kg']).'**'.($t['consommation_apres_leviers_pourcent'] ? ' → '.$t['consommation_apres_leviers_pourcent'].' % du budget' : '') : ''))->join("\n"),
            'recyclage_bilan' => '**Recyclage** : '.$r['demandes'].' demande(s), '.$r['en_cours'].' en cours. '.$kg($r['kg_collectes']).' de rebuts collectés → **'.$kg($r['kg_recycles']).'** de matière recyclée. '
                .'Frais payés : '.number_format($r['frais_payes_tnd'], 2, ',', ' ').' TND, avoirs reçus : '.number_format($r['avoirs_recus_tnd'], 2, ',', ' ').' TND.'
                .(($r['a_traiter_par_la_marque'] ?? []) !== [] ? "\nÀ faire : ".implode(' ; ', $r['a_traiter_par_la_marque']) : ''),
            'verifier_allegation' => isset($r['erreur']) ? $r['erreur'] : '**Greenwashing** : risque '.$r['niveau'].' ('.$r['risque'].'/100).'."\n".collect($r['phrases'])->map(fn ($p) => '- « '.$p['phrase'].' » → **'.$p['verdict'].'**'.($p['correction'] ? ' : '.$p['correction'] : ''))->join("\n"),
            default => '',
        };
    }
}
