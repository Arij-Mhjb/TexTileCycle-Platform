<?php

namespace App\Jobs;

use App\Models\Detection;
use App\Models\Scan;
use App\Services\MultimodalScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AnalyseMultimodalScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $scanId) {}

    public function handle(MultimodalScanService $multimodal): void
    {
        $scan = Scan::findOrFail($this->scanId);
        $analysis = $multimodal->analyse($scan);
        $scan->refresh()->load('product');

        if ($scan->product_id && $scan->product) {
            Detection::updateOrCreate(
                ['scan_id' => $scan->id, 'product_id' => $scan->product_id],
                [
                    'detected_name' => $analysis['product_name'] ?? $scan->product->name,
                    'detected_brand' => $analysis['brand'] ?? $scan->product->brand,
                    'detected_category' => $analysis['category'] ?? $scan->product->category,
                    'detected_composition' => json_encode($analysis['composition'] ?? [], JSON_UNESCAPED_UNICODE),
                    'confidence' => $analysis['confidence'] ?? $scan->confidence,
                    'is_confirmed' => false,
                ]
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        Scan::whereKey($this->scanId)->update([
            'status' => 'analysis_failed',
            'ai_status' => 'failed',
            'result_summary' => 'Analyse indisponible après plusieurs tentatives. Relancez-la depuis le scan.',
        ]);
    }
}
