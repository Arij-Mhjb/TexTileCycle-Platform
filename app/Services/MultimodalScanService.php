<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Scan;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class MultimodalScanService
{
    public function __construct(
        private MediaStorageService $media,
        private GeminiVisionService $gemini,
    ) {}

    private const MATERIAL_ALIASES = [
        'Viscose' => ['viscose', 'viskoz', 'viscosa', 'rayon'],
        'Polyester' => ['polyester', 'poliester'],
        'Élasthanne' => ['elasthanne', 'elastane', 'elastan', 'spandex', 'lycra'],
        'Coton' => ['coton', 'cotton', 'pamuk', 'algodon', 'algodao'],
        'Laine' => ['laine', 'wool', 'yun', 'yün', 'lana'],
        'Polyamide' => ['polyamide', 'poliamid', 'nylon'],
        'Acrylique' => ['acrylique', 'acrylic', 'akrilik'],
        'Lin' => ['lin', 'linen', 'keten', 'flax'],
        'Soie' => ['soie', 'silk', 'ipek', 'İpek'],
        'Modal' => ['modal'],
        'Lyocell' => ['lyocell', 'tencel'],
    ];

    public function analyse(Scan $scan): array
    {
        $scan->load('assets');
        $sourceTexts = [];
        $visionImages = [];

        if ($scan->raw_text && $scan->source_type !== 'multimodal') {
            $sourceTexts['manual'] = $scan->raw_text;
        }

        $unavailableAssets = [];
        foreach ($scan->assets as $asset) {
            $temporaryPath = null;
            try {
                $temporaryPath = $this->media->downloadToTemporaryFile(
                    $asset->path,
                    $asset->storage_disk,
                    $asset->cloudinary_public_id,
                    $asset->secure_url,
                    $asset->delivery_type,
                    $asset->format,
                    $asset->version,
                );
                if (! $temporaryPath) {
                    $unavailableAssets[] = $asset->type;

                    continue;
                }

                $text = $this->extractText($temporaryPath, $asset->type);
                $barcode = $this->extractBarcode($temporaryPath);
                if ($barcode) {
                    $text = trim($text."\nBARCODE: ".$barcode);
                }

                $asset->update([
                    'extracted_text' => $text ?: null,
                    'confidence' => $text ? 80 : null,
                ]);

                if ($text) {
                    $sourceTexts[$asset->type] = trim(($sourceTexts[$asset->type] ?? '')."\n".$text);
                }
            } catch (Throwable $exception) {
                report($exception);
                $unavailableAssets[] = $asset->type;
            } finally {
                if ($temporaryPath && is_file($temporaryPath)) {
                    $visionImages[] = ['path' => $temporaryPath, 'type' => $asset->type];
                }
            }
        }

        $rawText = collect($sourceTexts)->filter()->join("\n");
        $product = $this->bestProductMatch($rawText);
        $data = $this->extractStructuredData($sourceTexts, $product);

        $aiStatus = 'local_analysis_completed';
        $aiApplied = false;
        try {
            if ($this->gemini->enabled()) {
                $aiStatus = 'gemini_analysis_completed';
                $suggestions = $this->gemini->suggest($visionImages, $data);
                if ($suggestions) {
                    $data = $this->mergeAiSuggestions($data, $suggestions);
                    $aiApplied = (bool) ($data['ai_applied'] ?? false);
                }
            }
        } catch (Throwable $exception) {
            report($exception);
            $aiStatus = 'gemini_failed_local_fallback';
            $data['warnings'][] = 'Les suggestions IA sont temporairement indisponibles. Les résultats OCR locaux restent utilisables.';
        } finally {
            foreach ($visionImages as $image) {
                if (is_file($image['path'])) {
                    @unlink($image['path']);
                }
            }
        }

        $warnings = $data['warnings'] ?? [];
        if ($unavailableAssets) {
            $warnings[] = 'Certaines images sont indisponibles : '.collect($unavailableAssets)->unique()->join(', ').'.';
            $data['warnings'] = $warnings;
        }
        if ($rawText === '' && ! $aiApplied) {
            $warnings[] = 'Aucun texte lisible détecté. Recadrez le ticket ou l’étiquette, évitez les reflets et relancez l’analyse.';
            $data['warnings'] = $warnings;
            $data['confidence'] = 0;
        }

        $explanation = match (true) {
            $aiApplied => 'OCR local et vision Gemini ont proposé les champs lisibles ou visuellement déductibles. Vérifiez chaque suggestion avant de confirmer.',
            $product !== null => 'Produit rapproché du catalogue et informations extraites par OCR local multilingue.',
            default => 'Informations extraites des documents. Sélectionnez un produit du catalogue ou confirmez les valeurs proposées.',
        };

        if ($warnings) {
            $explanation .= ' Vérification requise : '.implode(' ', $warnings);
        }

        $scan->update([
            'product_id' => $product?->id,
            'raw_text' => $rawText ?: $scan->raw_text,
            'status' => $product ? 'completed' : 'needs_confirmation',
            'ai_status' => $aiStatus,
            'confidence' => $data['confidence'],
            'extracted_data' => $data,
            'ai_explanation' => $explanation,
        ]);

        return $data;
    }

    /**
     * Gemini complète uniquement les valeurs absentes. Une donnée OCR/catalogue
     * existante reste toujours prioritaire et toute suggestion demeure à confirmer.
     */
    public function mergeAiSuggestions(array $local, array $suggestions): array
    {
        $fields = [
            'brand', 'product_name', 'sku', 'barcode', 'category', 'color', 'size',
            'manufacturing_country', 'retailer', 'purchase_date', 'purchase_price', 'currency', 'composition',
            'condition', 'defects',
        ];
        $sources = $local['field_sources'] ?? [];
        $confidences = $local['field_confidence'] ?? [];
        $evidence = $local['field_evidence'] ?? [];
        $applied = [];

        foreach ($fields as $field) {
            $localValue = $local[$field] ?? null;
            $suggestedValue = $suggestions[$field] ?? null;
            $suggestedConfidence = max(0, min(100, (int) data_get($suggestions, "field_confidence.{$field}", 0)));
            $minimumConfidence = in_array($field, ['sku', 'barcode', 'purchase_date', 'purchase_price', 'composition'], true)
                ? 80
                : 60;
            $missing = $localValue === null
                || $localValue === ''
                || $localValue === []
                || ($field === 'condition' && $localValue === 'unknown');
            $usable = ! ($suggestedValue === null || $suggestedValue === '' || $suggestedValue === [])
                && ! ($field === 'condition' && $suggestedValue === 'unknown')
                && $suggestedConfidence >= $minimumConfidence
                && $this->validAiSuggestion($field, $suggestedValue);

            if ($missing && $usable) {
                $local[$field] = $suggestedValue;
                $sources[$field] = 'gemini';
                $confidences[$field] = $suggestedConfidence;
                $evidence[$field] = data_get($suggestions, "field_evidence.{$field}");
                $applied[] = $field;
            } elseif (! $missing) {
                $sources[$field] = $sources[$field] ?? 'local';
            }
        }

        $local['field_sources'] = $sources;
        $local['field_confidence'] = $confidences;
        $local['field_evidence'] = $evidence;
        $local['ai_suggestions'] = collect($suggestions)->only($fields)->all();
        $local['ai_applied_fields'] = $applied;
        $local['ai_applied'] = $applied !== [];

        if ($applied !== []) {
            $local['analysis_mode'] = 'local_ocr_plus_gemini_vision';
            $aiConfidence = collect($applied)
                ->map(fn ($field) => $confidences[$field] ?? 0)
                ->filter(fn ($value) => $value > 0)
                ->avg();
            if ($aiConfidence) {
                $local['confidence'] = min(95, (int) round(((int) ($local['confidence'] ?? 0) + $aiConfidence) / 2));
            }
        }

        return $local;
    }

    private function validAiSuggestion(string $field, mixed $value): bool
    {
        return match ($field) {
            'barcode' => is_string($value)
                && ctype_digit($value)
                && in_array(strlen($value), [8, 12, 13, 14], true),
            'purchase_date' => $this->validIsoDate($value),
            'purchase_price' => is_numeric($value) && (float) $value >= 0,
            'currency' => is_string($value) && preg_match('/^[A-Z]{3}$/', $value) === 1,
            'condition' => in_array($value, ['good', 'wearable', 'minor_damage', 'damaged', 'unusable'], true),
            'composition' => is_array($value) && collect($value)->every(fn ($item) =>
                is_array($item)
                && filled($item['material'] ?? null)
                && is_numeric($item['percentage'] ?? null)
                && (float) $item['percentage'] >= 0
                && (float) $item['percentage'] <= 100
            ),
            'defects' => is_array($value) && collect($value)->every(fn ($item) => is_string($item)),
            default => is_string($value) || is_numeric($value),
        };
    }

    private function validIsoDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    /**
     * Transforme les textes OCR en données structurées sans exiger une
     * correspondance préalable dans le catalogue.
     */
    public function extractStructuredData(array $sourceTexts, ?Product $product = null): array
    {
        $labelText = (string) ($sourceTexts['composition_label'] ?? '');
        $receiptText = (string) ($sourceTexts['receipt'] ?? '');
        $productText = trim(implode("\n", array_filter([
            $sourceTexts['product_front'] ?? '',
            $sourceTexts['product_back'] ?? '',
        ])));
        $allText = collect($sourceTexts)->filter()->join("\n");

        $label = $this->labelFields($labelText);
        $receipt = $this->receiptFields($receiptText);
        $composition = $this->composition($labelText ?: $allText, $product);
        $warnings = [];

        $labelBrand = $label['brand'] ?? null;
        $receiptBrand = $receipt['brand'] ?? null;
        if ($labelBrand && $receiptBrand && ! $this->sameValue($labelBrand, $receiptBrand)) {
            $warnings[] = "La marque de l’étiquette ({$labelBrand}) diffère de celle du ticket ({$receiptBrand}).";
        }

        $productPhotoBrand = $this->knownBrandFromText($productText);
        if ($labelBrand && $productPhotoBrand && ! $this->sameValue($labelBrand, $productPhotoBrand)) {
            $warnings[] = "Le logo lu sur le produit ({$productPhotoBrand}) diffère de l’étiquette ({$labelBrand}).";
        }

        $data = [
            'brand' => $product?->brand ?? $labelBrand ?? $productPhotoBrand ?? $receiptBrand,
            'product_name' => $product?->name ?? ($receipt['product_name'] ?? null),
            'sku' => $product?->sku ?? ($receipt['sku'] ?? $label['sku'] ?? null),
            'barcode' => $product?->barcode ?? ($label['barcode'] ?? $receipt['barcode'] ?? null),
            'category' => $product?->category ?? $this->category($receipt['product_name'] ?? null),
            'color' => null,
            'size' => $label['size'] ?? null,
            'manufacturing_country' => $label['manufacturing_country'] ?? null,
            'retailer' => $receipt['retailer'] ?? null,
            'purchase_date' => $receipt['purchase_date'] ?? null,
            'purchase_price' => $receipt['purchase_price'] ?? null,
            'currency' => $receipt['currency'] ?? null,
            'composition' => $composition,
            'condition' => 'unknown',
            'defects' => [],
            'warnings' => $warnings,
            'evidence' => [
                'label_brand' => $labelBrand,
                'receipt_brand' => $receiptBrand,
                'product_photo_brand' => $productPhotoBrand,
                'sources_read' => array_values(array_keys(array_filter($sourceTexts))),
            ],
            'analysis_mode' => 'local_multilingual_ocr_catalogue',
        ];

        $recognized = collect([
            'brand', 'product_name', 'sku', 'barcode', 'category', 'size', 'manufacturing_country',
            'retailer', 'purchase_date', 'purchase_price',
        ])->filter(fn ($field) => filled($data[$field] ?? null))->count();

        if ($composition) {
            $recognized += 2;
        }

        $confidence = min(95, 25 + ($recognized * 7) + ($product ? 12 : 0));
        if ($warnings) {
            $confidence = max(20, $confidence - (count($warnings) * 12));
        }

        $data['confidence'] = $confidence;

        return $data;
    }

    private function extractText(string $absolutePath, string $assetType): string
    {
        $temporaryPath = null;

        try {
            if (! is_file($absolutePath)) {
                return '';
            }

            $pageModes = match ($assetType) {
                'receipt' => [6],
                'composition_label' => [6, 11],
                'product_front', 'product_back' => [11],
                default => [],
            };

            $passes = collect($pageModes)
                ->map(fn ($mode) => $this->ocrPass($absolutePath, $mode))
                ->all();

            if ($assetType === 'composition_label') {
                $temporaryPath = sys_get_temp_dir().'/textilecycle-ocr-'.Str::uuid().'.png';
                $preprocess = new Process([
                    'convert', $absolutePath,
                    '-auto-orient',
                    '-colorspace', 'Gray',
                    '-contrast-stretch', '2%x2%',
                    '-sharpen', '0x1',
                    '-resize', '180%',
                    $temporaryPath,
                ]);
                $preprocess->setTimeout(30);
                $preprocess->run();

                if ($preprocess->isSuccessful() && is_file($temporaryPath)) {
                    $passes[] = $this->ocrPass($temporaryPath, 6);
                    $passes[] = $this->ocrPass($temporaryPath, 11);
                }
            }

            return collect($passes)
                ->filter()
                ->flatMap(fn ($text) => preg_split('/\R/u', $text))
                ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
                ->filter()
                ->unique()
                ->join("\n");
        } catch (Throwable $exception) {
            report($exception);

            return '';
        } finally {
            if ($temporaryPath && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private function ocrPass(string $path, int $pageSegmentationMode): string
    {
        $process = new Process([
            'tesseract', $path, 'stdout',
            '-l', 'fra+eng+tur',
            '--psm', (string) $pageSegmentationMode,
            '-c', 'preserve_interword_spaces=1',
        ]);
        $process->setTimeout(45);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : '';
    }

    private function extractBarcode(string $absolutePath): ?string
    {
        try {
            if (! is_file($absolutePath)) {
                return null;
            }

            $process = new Process(['zbarimg', '--quiet', '--raw', $absolutePath]);
            $process->setTimeout(15);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            return collect(preg_split('/\R/u', trim($process->getOutput())))
                ->map(fn ($value) => preg_replace('/\D+/', '', $value))
                ->first(fn ($value) => in_array(strlen($value), [8, 12, 13, 14], true));
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function bestProductMatch(string $text): ?Product
    {
        if ($text === '') {
            return null;
        }

        $haystack = $this->normalise($text);

        $candidate = Product::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->get()
            ->map(function (Product $product) use ($haystack) {
                $score = 0;
                foreach ([$product->barcode => 100, $product->qr_code => 100, $product->sku => 90] as $needle => $weight) {
                    if ($needle && str_contains($haystack, $this->normalise($needle))) {
                        $score = max($score, $weight);
                    }
                }
                foreach ([$product->name => 70, $product->brand => 35] as $needle => $weight) {
                    if ($needle && str_contains($haystack, $this->normalise($needle))) {
                        $score += $weight;
                    }
                }

                return ['product' => $product, 'score' => $score];
            })
            ->sortByDesc('score')
            ->first(fn ($item) => $item['score'] >= 70);

        return $candidate['product'] ?? null;
    }

    private function labelFields(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $barcode = $this->barcode($text);
        $size = $this->firstMatch(
            '/\b(?:SIZE|TAILLE|BEDEN|STZE)\s*[:\-]?\s*([A-Z0-9\/-]{1,10})\b/iu',
            $text
        );
        $sku = $this->firstMatch(
            '/\b(?:SKU|ARTICLE|ART\.?\s*NO|MODEL|KODU)\s*[:#\-]?\s*([A-Z0-9\/-]{4,30})\b/iu',
            $text
        );
        $manufacturingCountry = $this->firstMatch(
            '/\b(?:MADE\s+IN|FABRIQU[ÉE]\s+EN|MEN[SŞ]E[Iİ])\s*[:\-]?\s*([\p{L} .\'-]{2,40})/iu',
            $text
        );

        return [
            'brand' => $this->likelyLabelBrand($text),
            'barcode' => $barcode,
            'size' => $size ? Str::upper($size) : null,
            'sku' => $sku,
            'manufacturing_country' => $manufacturingCountry ? Str::headline(Str::lower($manufacturingCountry)) : null,
        ];
    }

    private function receiptFields(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $productName = $this->firstMatch(
            '/^\s*(?:MOD(?:ELE)?|ARTICLE|ITEM|PRODUIT)\s*[:\-]?\s*([^\r\n]{3,80})/imu',
            $text
        );

        $sku = $this->firstMatch(
            '/^\s*(?:MOD(?:ELE)?|ARTICLE|ITEM|PRODUIT)[^\r\n]*\R\s*([A-Z0-9-]{5,30})\s*$/imu',
            $text
        );

        $price = $this->firstMatch(
            '/(?:TOTAL\s+(?:PAY[ÉE]|PAID)|PRIX\s+NET|TOTAL\s+TTC)\D{0,20}(\d{1,6}(?:[.,]\d{2}))/iu',
            $text
        );

        $date = $this->firstMatch(
            '/\b(\d{2}[\/.-]\d{2}[\/.-]\d{4})\b/u',
            $text
        );

        return [
            'retailer' => $this->retailer($text),
            'product_name' => $productName ? Str::headline(Str::lower(trim($productName))) : null,
            'brand' => $this->brandFromProductName($productName),
            'sku' => $sku,
            'barcode' => $this->barcode($text),
            'purchase_price' => $price !== null ? (float) str_replace(',', '.', $price) : null,
            'currency' => $this->currency($text),
            'purchase_date' => $this->normaliseDate($date),
        ];
    }

    private function composition(string $text, ?Product $product): array
    {
        $materials = [];

        foreach (self::MATERIAL_ALIASES as $canonical => $aliases) {
            $alternatives = implode('|', array_map(
                fn ($alias) => preg_quote($alias, '/'),
                $aliases
            ));

            $patterns = [
                '/[%％]?\s*(\d{1,3}(?:[.,]\d+)?)\s*[%％]?\s*(?:'.$alternatives.')\b/iu',
                '/\b(?:'.$alternatives.')\s*[:\-]?\s*[%％]?\s*(\d{1,3}(?:[.,]\d+)?)\s*[%％]?/iu',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $match)) {
                    $percentage = (float) str_replace(',', '.', $match[1]);
                    if ($percentage >= 0 && $percentage <= 100) {
                        $materials[$canonical] = [
                            'material' => $canonical,
                            'percentage' => $percentage,
                        ];
                        break;
                    }
                }
            }
        }

        if ($materials) {
            return array_values($materials);
        }

        if (! $product) {
            return [];
        }

        $product->loadMissing('materials');

        return $product->materials->map(fn ($material) => [
            'material' => $material->name,
            'percentage' => $material->pivot->percentage,
        ])->values()->all();
    }

    private function barcode(string $text): ?string
    {
        preg_match_all('/(?<!\d)(?:\d[\s-]?){8,14}(?!\d)/u', $text, $matches);

        $candidates = collect($matches[0] ?? [])
            ->map(fn ($value) => preg_replace('/\D+/', '', $value))
            ->filter(fn ($value) => in_array(strlen($value), [8, 12, 13, 14], true))
            ->sortByDesc(fn ($value) => strlen($value) === 13 ? 100 : strlen($value));

        return $candidates->first();
    }

    private function likelyLabelBrand(string $text): ?string
    {
        $known = $this->knownBrandFromText($text);
        if ($known) {
            return $known;
        }

        $ignored = '/%|SIZE|TAILLE|BEDEN|STZE|MADE|FABRI|KUMA[SŞ]|VISKO|POL[Iİ]ESTER|ELAST|COTTON|PAMUK|WASH|YIKAY|ORD|KPN|SKU|ARTICLE|MODEL|\d{5,}/iu';

        $line = collect(preg_split('/\R/u', $text))
            ->map(fn ($value) => trim(preg_replace('/\s+/', ' ', $value)))
            ->filter(fn ($value) => mb_strlen($value) >= 3 && mb_strlen($value) <= 45)
            ->first(fn ($value) => ! preg_match($ignored, $value)
                && preg_match('/[\p{L}]{2,}/u', $value)
                && $this->uppercaseRatio($value) >= 0.55);

        return $line ? Str::headline(Str::lower($line)) : null;
    }

    private function knownBrandFromText(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        $brands = Product::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->pluck('brand')
            ->filter()
            ->unique();

        $normalised = $this->normalise($text);

        return $brands->first(fn ($brand) => str_contains($normalised, $this->normalise($brand)));
    }

    private function retailer(string $text): ?string
    {
        $retailers = [
            'printemps' => 'Printemps',
            'zara' => 'Zara',
            'h&m' => 'H&M',
            'decathlon' => 'Decathlon',
            'lc waikiki' => 'LC Waikiki',
            'monoprix' => 'Monoprix',
            'kiabi' => 'Kiabi',
            'bershka' => 'Bershka',
            'pull&bear' => 'Pull&Bear',
        ];

        $normalised = $this->normalise($text);
        foreach ($retailers as $needle => $name) {
            if (str_contains($normalised, $this->normalise($needle))) {
                return $name;
            }
        }

        return null;
    }

    private function brandFromProductName(?string $productName): ?string
    {
        if (! $productName) {
            return null;
        }

        $known = $this->knownBrandFromText($productName);
        if ($known) {
            return $known;
        }

        $tokens = preg_split('/\s+/u', trim($productName));
        if (count($tokens) < 2) {
            return null;
        }

        $candidate = end($tokens);

        return preg_match('/^[\p{L}][\p{L}\'’-]{2,}$/u', $candidate)
            ? Str::headline(Str::lower($candidate))
            : null;
    }

    private function category(?string $productName): ?string
    {
        if (! $productName) {
            return null;
        }

        $categories = [
            'blazer' => 'Veste',
            'jacket' => 'Veste',
            'veste' => 'Veste',
            'shirt' => 'Haut',
            'chemise' => 'Haut',
            't-shirt' => 'Haut',
            'sweat' => 'Haut',
            'jean' => 'Pantalon',
            'trouser' => 'Pantalon',
            'pantalon' => 'Pantalon',
            'dress' => 'Robe',
            'robe' => 'Robe',
            'skirt' => 'Jupe',
            'jupe' => 'Jupe',
        ];

        $normalised = $this->normalise($productName);
        foreach ($categories as $needle => $category) {
            if (str_contains($normalised, $needle)) {
                return $category;
            }
        }

        return null;
    }

    private function currency(string $text): ?string
    {
        return match (true) {
            str_contains($text, '€') || preg_match('/\beuros?\b/iu', $text) === 1 => 'EUR',
            preg_match('/\b(?:TND|DT|dinars?)\b/iu', $text) === 1 => 'TND',
            str_contains($text, '$') || preg_match('/\bUSD\b/iu', $text) === 1 => 'USD',
            default => null,
        };
    }

    private function normaliseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = str_replace(['.', '-'], '/', $date);
        $parsed = DateTimeImmutable::createFromFormat('d/m/Y', $date);

        return $parsed instanceof DateTimeImmutable ? $parsed->format('Y-m-d') : null;
    }

    private function firstMatch(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $match) ? trim($match[1]) : null;
    }

    private function sameValue(string $first, string $second): bool
    {
        $a = $this->normalise($first);
        $b = $this->normalise($second);

        return $a === $b || str_contains($a, $b) || str_contains($b, $a);
    }

    private function normalise(string $value): string
    {
        return Str::lower(Str::ascii(preg_replace('/\s+/', ' ', trim($value))));
    }

    private function uppercaseRatio(string $value): float
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $value);
        if ($letters === '') {
            return 0;
        }

        $uppercase = preg_replace('/[^\p{Lu}]/u', '', $letters);

        return mb_strlen($uppercase) / max(1, mb_strlen($letters));
    }
}

