<?php

namespace App\Services\Ai;

use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * IA du module Fournisseurs :
 *  1. lecture d'un certificat de provenance à partir d'une photo (Gemini Vision, sinon OCR Tesseract) ;
 *  2. analyse de risque d'un fournisseur (règles vérifiables + synthèse rédigée par Gemini).
 */
class SupplierAiService
{
    /** Certificats attendus selon la famille de matière. */
    private const EXPECTED = [
        'recycl' => ['GRS'], 'bio' => ['GOTS'], 'organic' => ['GOTS'], 'laine' => ['RWS'], 'wool' => ['RWS'],
        'lyocell' => ['FSC'], 'viscose' => ['FSC'], 'modal' => ['FSC'],
    ];

    public function __construct(private GeminiJsonClient $gemini) {}

    /**
     * @return array{fields: array<string, mixed>, confidence: int, warnings: array<int, string>, source: string}
     */
    public function extractCertificate(string $imagePath, SupplierBatch $batch): array
    {
        $batch->loadMissing(['material', 'supplier']);
        $fields = null;
        $source = 'gemini';

        $description = null;
        $answer = $this->gemini->ask($this->certificatePrompt($batch), $this->certificateSchema(), [$imagePath]);
        if ($answer) {
            $description = filled($answer['image_description'] ?? null) ? (string) $answer['image_description'] : null;
            if (($answer['is_certificate'] ?? true) === false) {
                return ['fields' => [], 'confidence' => (int) ($answer['confidence'] ?? 0), 'warnings' => [], 'source' => 'gemini', 'is_certificate' => false,
                    'document_kind' => (string) ($answer['document_kind'] ?? 'document non identifié'),
                    'description' => $description ?? 'L’image montre : '.($answer['document_kind'] ?? 'un document non identifié').'.'];
            }
            $fields = collect($answer)->only(['type', 'certificate_number', 'issuer', 'issued_at', 'expires_at', 'material', 'holder', 'confidence'])->all();
        } else {
            $source = 'ocr';
            [$fields, $description] = $this->ocrFallback($imagePath);
        }

        if ($fields === null) {
            return ['fields' => [], 'confidence' => 0, 'warnings' => ['L’IA n’a pas pu lire ce document : TexTileCycle vérifiera le certificat directement sur le document envoyé.'], 'source' => 'none',
                'description' => $description ?? 'Analyse locale : aucun texte lisible détecté dans l’image.'];
        }

        $fields['type'] = $this->normaliseType($fields['type'] ?? null);

        return [
            'fields' => $fields,
            'confidence' => (int) ($fields['confidence'] ?? ($source === 'ocr' ? 55 : 70)),
            'warnings' => $this->certificateWarnings($fields, $batch),
            'source' => $source,
            'is_certificate' => true,
            'description' => $description,
        ];
    }

    /**
     * @return array{score: int, level: string, risks: array<int, array{severity: string, text: string}>, recommendations: array<int, string>, summary: string, source: string, analysed_at: string}
     */
    public function analyseRisk(Supplier $supplier): array
    {
        $supplier->loadMissing(['batches.material', 'batches.certificates', 'batches.productLinks.product.materials']);
        $risks = $this->riskFindings($supplier);

        $score = (int) min(100, collect($risks)->sum(fn ($risk) => ['high' => 25, 'medium' => 12, 'low' => 5][$risk['severity']]));
        $level = $score >= 50 ? 'élevé' : ($score >= 20 ? 'moyen' : 'faible');
        $recommendations = $this->localRecommendations($risks);
        $summary = 'Risque '.$level.' : '.count($risks).' point(s) d’attention sur '.$supplier->batches->count().' lot(s).';
        $source = 'local';

        $answer = $this->gemini->ask($this->riskPrompt($supplier, $risks, $score), [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'recommendations' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 5],
            ],
            'required' => ['summary', 'recommendations'],
        ]);
        if ($answer && filled($answer['summary'] ?? null)) {
            $summary = (string) $answer['summary'];
            $recommendations = array_values(array_filter((array) ($answer['recommendations'] ?? []), 'is_string')) ?: $recommendations;
            $source = 'gemini';
        }

        $result = compact('score', 'level', 'risks', 'recommendations', 'summary', 'source') + ['analysed_at' => now()->toIso8601String()];
        $supplier->update(['ai_risk' => $result]);

        return $result;
    }

    /** @return array<int, array{severity: string, text: string}> */
    private function riskFindings(Supplier $supplier): array
    {
        $risks = [];
        if ($supplier->status === 'suspended') {
            $risks[] = ['severity' => 'high', 'text' => 'Fournisseur suspendu par la marque.'];
        }
        if ($supplier->batches->isEmpty()) {
            $risks[] = ['severity' => 'medium', 'text' => 'Aucun lot déclaré : la provenance des matières n’est pas documentée.'];
        }

        foreach ($supplier->batches as $batch) {
            $certificates = $batch->certificates;
            if ($batch->status === 'rejected') {
                $risks[] = ['severity' => 'high', 'text' => 'Lot '.$batch->batch_code.' refusé par TexTileCycle.'];
            } elseif ($batch->status !== 'verified') {
                $risks[] = ['severity' => 'low', 'text' => 'Lot '.$batch->batch_code.' pas encore vérifié.'];
            }
            if ($certificates->isEmpty()) {
                $risks[] = ['severity' => 'medium', 'text' => 'Lot '.$batch->batch_code.' sans certificat de provenance.'];
            }
            foreach ($certificates->reject->isComplete() as $certificate) {
                $risks[] = ['severity' => 'low', 'text' => 'Certificat du lot '.$batch->batch_code.' illisible par l’IA : vérification manuelle du document nécessaire.'];
            }
            foreach ($certificates as $certificate) {
                if ($certificate->isExpired()) {
                    $risks[] = ['severity' => 'high', 'text' => $certificate->displayName().' expiré.'];
                } elseif ($certificate->expires_at && $certificate->expires_at->lt(now()->addMonths(2))) {
                    $risks[] = ['severity' => 'low', 'text' => $certificate->displayName().' expire le '.$certificate->expires_at->format('d/m/Y').'.'];
                }
                if ($certificate->status === 'rejected') {
                    $risks[] = ['severity' => 'high', 'text' => $certificate->displayName().' refusé.'];
                }
            }
            $expected = $this->expectedTypes($batch->material?->name);
            if ($expected && $certificates->isNotEmpty() && ! $certificates->pluck('type')->filter()->intersect($expected)->count()) {
                $risks[] = ['severity' => 'medium', 'text' => 'Lot '.$batch->batch_code.' ('.$batch->material->name.') : certificat '.implode(' ou ', $expected).' attendu, '.$certificates->pluck('type')->filter()->unique()->join(', ').' fourni.'];
            }
            foreach ($batch->productLinks->where('conformity_status', 'mismatch') as $link) {
                $risks[] = ['severity' => 'medium', 'text' => 'Écart de conformité avec le produit « '.$link->product?->name.' ».'];
            }
        }

        return $risks;
    }

    /** @return array<int, string> */
    private function localRecommendations(array $risks): array
    {
        $texts = collect($risks)->pluck('text')->join(' ');
        $recommendations = [];
        if (Str::contains($texts, 'expir')) {
            $recommendations[] = 'Demander au fournisseur le renouvellement des certificats expirés ou proches de l’expiration.';
        }
        if (Str::contains($texts, 'sans certificat')) {
            $recommendations[] = 'Exiger un certificat de provenance pour chaque lot avant de l’associer à un produit.';
        }
        if (Str::contains($texts, 'attendu')) {
            $recommendations[] = 'Vérifier que le référentiel du certificat correspond à la matière (GRS pour le recyclé, GOTS pour le bio, RWS pour la laine).';
        }
        if (Str::contains($texts, ['Écart', 'refusé'])) {
            $recommendations[] = 'Corriger les déclarations produit ou remplacer les lots non conformes.';
        }
        if (Str::contains($texts, 'pas encore vérifié')) {
            $recommendations[] = 'Relancer la vérification TexTileCycle des lots en attente.';
        }

        return $recommendations ?: ['Aucun risque majeur : maintenir le suivi des certificats à chaque nouvelle commande.'];
    }

    /** @return array<int, string> */
    private function certificateWarnings(array $fields, SupplierBatch $batch): array
    {
        $warnings = [];
        if (empty($fields['certificate_number'])) {
            $warnings[] = 'Numéro de certificat non lisible : vérifiez-le sur le document.';
        }
        if (! empty($fields['expires_at']) && strtotime((string) $fields['expires_at']) < time()) {
            $warnings[] = 'Le certificat semble expiré ('.$fields['expires_at'].').';
        }
        $expected = $this->expectedTypes($batch->material?->name);
        if ($expected && ! empty($fields['type']) && ! in_array($fields['type'], $expected, true)) {
            $warnings[] = 'Pour « '.$batch->material->name.' », un certificat '.implode(' ou ', $expected).' est attendu ; le document indique '.$fields['type'].'.';
        }
        if (! empty($fields['material']) && $batch->material && ! Str::contains(Str::lower(Str::ascii($fields['material'])), Str::lower(Str::ascii(Str::before($batch->material->name, ' '))))) {
            $warnings[] = 'Matière lue « '.$fields['material'].' » différente de la matière du lot « '.$batch->material->name.' ».';
        }
        if (! empty($fields['holder']) && ! Str::contains(Str::lower($fields['holder']), Str::lower(Str::before($batch->supplier->name, ' ')))) {
            $warnings[] = 'Titulaire du certificat « '.$fields['holder'].' » : vérifiez qu’il s’agit bien de '.$batch->supplier->name.'.';
        }

        return $warnings;
    }

    /** @return array<int, string> */
    private function expectedTypes(?string $material): array
    {
        $name = Str::lower(Str::ascii((string) $material));
        foreach (self::EXPECTED as $keyword => $types) {
            if ($name !== '' && Str::contains($name, $keyword)) {
                return $types;
            }
        }

        return [];
    }

    private function normaliseType(?string $type): ?string
    {
        $value = Str::upper((string) $type);
        foreach (array_keys(MaterialOriginCertificate::TYPES) as $known) {
            if ($value !== '' && Str::contains($value, Str::upper($known))) {
                return $known;
            }
        }
        if (Str::contains($value, ['OEKO', 'OKO'])) {
            return 'OEKO-TEX';
        }

        return $type ? (Str::contains(Str::lower($type), 'origin') ? 'origin' : 'other') : null;
    }

    /** OCR local (Tesseract) + expressions régulières, si Gemini n'est pas disponible. */
    /** @return array{0: ?array, 1: ?string} [champs lus, description de l'image] */
    private function ocrFallback(string $imagePath): array
    {
        $binary = (new ExecutableFinder)->find('tesseract');
        if (! $binary) {
            return [null, null];
        }
        $text = '';
        foreach (['fra+eng', 'eng'] as $languages) {
            $process = new Process([$binary, $imagePath, 'stdout', '-l', $languages]);
            $process->setTimeout(40);
            $process->run();
            if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                $text = $process->getOutput();
                break;
            }
        }
        if (trim($text) === '') {
            return [null, 'Analyse locale (sans Gemini) : aucun texte lisible dans l’image.'];
        }
        $excerpt = Str::limit(preg_replace('/\s+/', ' ', trim($text)), 140);
        $description = 'Analyse locale (sans Gemini) : texte lu dans l’image « '.$excerpt.' ».';

        preg_match('/\b(GOTS|GRS|RWS|FSC|OEKO[- ]?TEX)\b/i', $text, $type);
        preg_match('/(?:n[°o]|number|num[ée]ro)[ \t]*[:#.]?[ \t]*([A-Z0-9][A-Z0-9\-\/\.]*\d[A-Z0-9\-\/\.]*)/i', $text, $number);
        if (empty($number[1])) {
            preg_match('/\b([A-Z]{2,}[-\/][A-Z0-9\-\/]*\d{4,}[A-Z0-9\-\/]*)\b/', $text, $number);
        }
        preg_match('/(?:holder|titulaire)[ \t]*:[ \t]*([^\n,]+)/i', $text, $holder);
        preg_match('/(?:product|produit|mati[èe]re|material)[ \t]*:[ \t]*([^\n]+)/i', $text, $material);
        preg_match_all('/\b(\d{4}-\d{2}-\d{2}|\d{2}[\/.]\d{2}[\/.]\d{4})\b/', $text, $dates);
        preg_match('/(Control Union|Ecocert|Bureau Veritas|SGS|Intertek|Textile Exchange|OEKO-TEX Association|CU\s?India)/i', $text, $issuer);
        $dates = collect($dates[1] ?? [])->map(fn ($date) => date('Y-m-d', strtotime(str_replace(['/', '.'], '-', $date))))->sort()->values();

        return [[
            'type' => $type[1] ?? null,
            'certificate_number' => $number[1] ?? null,
            'issuer' => $issuer[1] ?? null,
            'issued_at' => $dates->first(),
            'expires_at' => $dates->count() > 1 ? $dates->last() : null,
            'material' => isset($material[1]) ? trim($material[1]) : null,
            'holder' => isset($holder[1]) ? trim($holder[1]) : null,
        ], $description];
    }

    private function certificatePrompt(SupplierBatch $batch): string
    {
        return <<<PROMPT
Tu lis la photo d'un certificat textile (GOTS, GRS, RWS, OEKO-TEX, FSC ou certificat d'origine).
Dans tous les cas, décris dans "image_description" (1 à 2 phrases en français) ce que montre réellement l'image :
type de document ou de photo, éléments visibles, texte principal lisible.
"is_certificate" : true seulement si l'image est réellement un certificat ou une attestation de provenance/conformité textile ;
sinon false, et résume en quelques mots dans "document_kind" ce que c'est (ex. « diagramme UML », « photo d'un vêtement »).
Extrais uniquement ce qui est réellement lisible ; mets null sinon. N'invente jamais un numéro ni une date.
Dates au format YYYY-MM-DD. "type" doit être l'un de : GOTS, GRS, RWS, OEKO-TEX, FSC, origin, other.
"confidence" (0 à 100) : pour un certificat, la fiabilité de la lecture des informations ;
sinon, ta certitude que l'image n'est pas un certificat.
Contexte : le lot {$batch->batch_code} déclare la matière « {$batch->material?->name} » fournie par « {$batch->supplier?->name} » ({$batch->origin_country}).
PROMPT;
    }

    private function certificateSchema(): array
    {
        $nullable = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'type' => $nullable, 'certificate_number' => $nullable, 'issuer' => $nullable,
                'issued_at' => $nullable, 'expires_at' => $nullable, 'material' => $nullable, 'holder' => $nullable,
                'confidence' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                'is_certificate' => ['type' => 'boolean'],
                'document_kind' => ['type' => 'string'],
                'image_description' => ['type' => 'string'],
            ],
            'required' => ['is_certificate', 'document_kind', 'image_description', 'type', 'certificate_number', 'issuer', 'issued_at', 'expires_at', 'material', 'holder', 'confidence'],
        ];
    }

    private function riskPrompt(Supplier $supplier, array $risks, int $score): string
    {
        $facts = json_encode([
            'fournisseur' => $supplier->name, 'pays' => $supplier->country, 'statut' => $supplier->status,
            'score_risque' => $score, 'constats' => $risks,
            'lots' => $supplier->batches->map(fn ($batch) => [
                'code' => $batch->batch_code, 'matiere' => $batch->material?->name, 'origine' => $batch->origin_country,
                'statut' => $batch->status, 'certificats' => $batch->certificates->map(fn ($c) => $c->type.' '.$c->statusLabel())->all(),
            ])->all(),
        ], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Tu es auditeur RSE dans le textile. À partir des constats vérifiés ci-dessous (ne les modifie pas, n'invente aucun fait),
rédige en français une synthèse de 2 phrases du risque fournisseur et au maximum 5 recommandations concrètes et priorisées pour la marque.
Données : {$facts}
PROMPT;
    }
}
