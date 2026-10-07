<?php

namespace Tests\Feature;

use App\Services\GeminiVisionService;
use App\Services\MultimodalScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultimodalScanExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_extracts_turkish_label_and_french_receipt_fields(): void
    {
        $data = app(MultimodalScanService::class)->extractStructuredData([
            'composition_label' => <<<'TEXT'
LC WAIKIKI
ATL,GROS-E
SIZE:S
%92 VISKOZ / %7 POLIESTER / %1 ELASTAN
8 684143 465678
TEXT,
            'receipt' => <<<'TEXT'
PRINTEMPS
MOD BLAZER MONCLER
462943203
Prix Net 580,00
Total payé 580,00
14/07/2020 - 16:21:47
Montants exprimés en euros
TEXT,
        ]);

        $this->assertSame('Lc Waikiki', $data['brand']);
        $this->assertSame('Blazer Moncler', $data['product_name']);
        $this->assertSame('462943203', $data['sku']);
        $this->assertSame('8684143465678', $data['barcode']);
        $this->assertSame('S', $data['size']);
        $this->assertSame('Printemps', $data['retailer']);
        $this->assertSame('2020-07-14', $data['purchase_date']);
        $this->assertSame(580.0, $data['purchase_price']);
        $this->assertSame('EUR', $data['currency']);

        $composition = collect($data['composition'])->keyBy('material');
        $this->assertSame(92.0, $composition['Viscose']['percentage']);
        $this->assertSame(7.0, $composition['Polyester']['percentage']);
        $this->assertSame(1.0, $composition['Élasthanne']['percentage']);

        $this->assertNotEmpty($data['warnings']);
        $this->assertStringContainsString('diffère', $data['warnings'][0]);
    }

    public function test_it_extracts_common_material_names_in_multiple_languages(): void
    {
        $data = app(MultimodalScanService::class)->extractStructuredData([
            'composition_label' => '80% PAMUK 15% POLYESTER 5% ELASTAN',
        ]);

        $composition = collect($data['composition'])->keyBy('material');
        $this->assertSame(80.0, $composition['Coton']['percentage']);
        $this->assertSame(15.0, $composition['Polyester']['percentage']);
        $this->assertSame(5.0, $composition['Élasthanne']['percentage']);
    }

    public function test_gemini_only_fills_missing_fields_and_preserves_local_values(): void
    {
        $service = app(MultimodalScanService::class);
        $merged = $service->mergeAiSuggestions([
            'barcode' => '8684143465678',
            'category' => null,
            'color' => null,
            'condition' => 'unknown',
            'composition' => [],
            'defects' => [],
            'confidence' => 32,
        ], [
            'barcode' => '1111111111111',
            'category' => 'Veste',
            'color' => 'Noir',
            'condition' => 'good',
            'composition' => [['material' => 'Viscose', 'percentage' => 92]],
            'defects' => [],
            'field_confidence' => [
                'barcode' => 99,
                'category' => 88,
                'color' => 84,
                'condition' => 72,
                'composition' => 94,
            ],
            'field_evidence' => [
                'category' => 'Silhouette de blazer visible sur la photo produit.',
                'color' => 'Couleur dominante noire.',
            ],
        ]);

        $this->assertSame('8684143465678', $merged['barcode']);
        $this->assertSame('local', $merged['field_sources']['barcode']);
        $this->assertSame('Veste', $merged['category']);
        $this->assertSame('Noir', $merged['color']);
        $this->assertSame('good', $merged['condition']);
        $this->assertSame('gemini', $merged['field_sources']['category']);
        $this->assertContains('composition', $merged['ai_applied_fields']);
        $this->assertTrue($merged['ai_applied']);
    }

    public function test_gemini_vision_uses_structured_json_and_an_api_key_header(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.model', 'gemini-3.5-flash-lite');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'interaction' => [
                    'output_text' => json_encode([
                        'category' => 'Veste',
                        'color' => 'Noir',
                        'field_confidence' => ['category' => 91, 'color' => 87],
                        'field_evidence' => ['category' => 'Photo produit', 'color' => 'Photo produit'],
                    ]),
                ],
            ]),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'textilecycle-test-');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        try {
            $result = app(GeminiVisionService::class)->suggest([
                ['path' => $path, 'type' => 'product_front'],
            ]);
        } finally {
            @unlink($path);
        }

        $this->assertSame('Veste', $result['category']);
        $this->assertSame('Noir', $result['color']);
        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'test-key')
                && $request['model'] === 'gemini-3.5-flash-lite'
                && data_get($request->data(), 'response_format.mime_type') === 'application/json';
        });
    }
}

