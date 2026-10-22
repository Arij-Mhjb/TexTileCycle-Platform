<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductWorkflowService
{
    public function saveVersion(Product $product, int $userId, string $reason): void
    {
        $product->load('materials');
        $version = $product->current_version ?: 1;
        $product->versions()->updateOrCreate(['version' => $version], [
            'created_by' => $userId, 'reason' => $reason,
            'snapshot' => ['product' => $product->only(['name', 'brand', 'sku', 'barcode', 'qr_code', 'category', 'description', 'origin', 'status', 'recyclability_score', 'recycling_instructions']),
                'materials' => $product->materials->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'percentage' => $m->pivot->percentage, 'component' => $m->pivot->component, 'origin' => $m->pivot->origin_country])->values()->all()],
        ]);
    }

    public function submit(Product $product, int $userId): void
    {
        $result = app(ProductCompletenessService::class)->refresh($product);
        if (! $result['ready']) {
            throw ValidationException::withMessages(['product' => 'Le produit doit être complet à 100 % avant soumission.']);
        }
        $this->saveVersion($product, $userId, 'Soumission pour vérification');
        $product->update(['status' => 'review', 'submitted_at' => now(), 'review_comment' => null]);
    }

    public function publish(Product $product, int $userId): void
    {
        $result = app(ProductCompletenessService::class)->refresh($product);
        if (! $result['ready']) {
            throw ValidationException::withMessages(['product' => 'Produit incomplet.']);
        }
        $product->increment('current_version');
        $product->update(['status' => 'published', 'is_active' => true, 'verified_at' => now(), 'published_at' => now(), 'review_comment' => null]);
        $this->saveVersion($product, $userId, 'Publication');
    }

    public function archive(Product $product): void
    {
        $product->update(['status' => 'archived', 'is_active' => false, 'archived_at' => now()]);
    }
}
