<?php

namespace Tests\Feature;

use App\Services\MultimodalScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
