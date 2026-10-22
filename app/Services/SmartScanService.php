<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class SmartScanService
{
    public function matches(string $value): Collection
    {
        $value = trim($value);
        if ($value === '') return collect();

        return Product::query()
            ->with(['materials', 'brandProfile'])
            ->where('is_active', true)
            ->where(function ($query) use ($value) {
                $query->where('barcode', $value)
                    ->orWhere('qr_code', $value)
                    ->orWhere('sku', $value)
                    ->orWhere('name', 'like', '%'.$value.'%')
                    ->orWhere('brand', 'like', '%'.$value.'%');
            })
            ->limit(5)->get();
    }

    public function confidence(Product $product, string $value): float
    {
        $value = mb_strtolower(trim($value));
        if (in_array($value, array_filter([
            mb_strtolower((string) $product->barcode),
            mb_strtolower((string) $product->qr_code),
            mb_strtolower((string) $product->sku),
        ]), true)) return 100;
        return round(max(55, 95 - levenshtein($value, mb_strtolower($product->name))), 2);
    }
}
