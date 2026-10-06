<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Scan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class MultimodalScanService
{
    public function analyse(Scan $scan): array
    {
        $scan->load('assets');
        $texts = collect([$scan->raw_text])->filter();

        foreach ($scan->assets as $asset) {
            $text = $this->extractText($asset->path);
            $asset->update([
                'extracted_text' => $text ?: null,
                'confidence' => $text ? 80 : null,
            ]);
            if ($text) {
                $texts->push($text);
            }
        }

        $rawText = $texts->join("\n");
        $product = $this->bestProductMatch($rawText);
        $composition = $this->composition($rawText, $product);

        $data = [
            'brand' => $product?->brand,
            'product_name' => $product?->name,
            'sku' => $product?->sku,
            'barcode' => $product?->barcode,
            'category' => $product?->category,
            'color' => null,
            'size' => null,
            'retailer' => null,
            'purchase_date' => null,
            'composition' => $composition,
            'condition' => 'unknown',
            'defects' => [],
            'confidence' => $product ? 82 : ($rawText ? 45 : 0),
            'analysis_mode' => 'local_ocr_catalogue',
        ];

        $scan->update([
            'product_id' => $product?->id,
            'raw_text' => $rawText ?: $scan->raw_text,
            'status' => $product ? 'completed' : 'needs_confirmation',
            'ai_status' => 'local_analysis_completed',
            'confidence' => $data['confidence'],
            'extracted_data' => $data,
            'ai_explanation' => $product
                ? 'Produit rapproché du catalogue par OCR local. Les matières restent déclaratives jusqu’au contrôle physique.'
                : 'Les photos ont été conservées pour contrôle. Complétez les champs non reconnus avant le dépôt.',
        ]);

        return $data;
    }

    private function extractText(string $path): string
    {
        try {
            $absolutePath = Storage::disk('public')->path($path);
            if (! is_file($absolutePath)) {
                return '';
            }

            $process = new Process(['tesseract', $absolutePath, 'stdout', '-l', 'fra+eng']);
            $process->setTimeout(45);
            $process->run();

            return $process->isSuccessful() ? trim($process->getOutput()) : '';
        } catch (Throwable $exception) {
            report($exception);

            return '';
        }
    }

    private function bestProductMatch(string $text): ?Product
    {
        if ($text === '') {
            return null;
        }

        $haystack = Str::lower(Str::ascii($text));

        $candidate = Product::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->get()
            ->map(function (Product $product) use ($haystack) {
                $score = 0;
                foreach ([$product->barcode => 100, $product->qr_code => 100, $product->sku => 90] as $needle => $weight) {
                    if ($needle && str_contains($haystack, Str::lower(Str::ascii($needle)))) {
                        $score = max($score, $weight);
                    }
                }
                foreach ([$product->name => 70, $product->brand => 35] as $needle => $weight) {
                    if ($needle && str_contains($haystack, Str::lower(Str::ascii($needle)))) {
                        $score += $weight;
                    }
                }

                return ['product' => $product, 'score' => $score];
            })
            ->sortByDesc('score')
            ->first(fn ($item) => $item['score'] >= 70);

        return $candidate['product'] ?? null;
    }

    private function composition(string $text, ?Product $product): array
    {
        preg_match_all('/(\d{1,3}(?:[.,]\d+)?)\s*%\s*([\p{L}][\p{L}\s-]{2,30})/u', $text, $matches, PREG_SET_ORDER);

        if ($matches) {
            return collect($matches)->map(fn ($match) => [
                'material' => trim($match[2]),
                'percentage' => (float) str_replace(',', '.', $match[1]),
            ])->values()->all();
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
}
