<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\MaterialMatch;
use App\Models\MaterialRequest;
use App\Models\ProductReturn;

class MaterialMatchingService
{
    public function __construct(private readonly AiMaterialCompatibilityService $compatibility) {}

    public function match(ProductReturn $return): int
    {
        $return->loadMissing(['scan', 'product.materials']);
        $created = 0;

        $requests = MaterialRequest::query()
            ->where('status', 'open')
            ->where('remaining_kg', '>', 0)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('brand.user')
            ->get();

        foreach ($requests as $request) {
            $created += $this->assess($return, $request);
        }

        return $created;
    }

    /** Analyse aussi les retours déjà vérifiés quand la marque publie sa demande après leur arrivée. */
    public function matchRequest(MaterialRequest $request): int
    {
        $request->loadMissing('brand.user');
        $created = 0;

        $returns = ProductReturn::query()
            ->where('status', 'marketplace')
            ->where('admin_review_status', 'verified')
            ->with(['scan', 'product.materials'])
            ->latest('admin_verified_at')
            ->limit(100)
            ->get();

        foreach ($returns as $return) {
            $created += $this->assess($return, $request);
        }

        return $created;
    }

    private function assess(ProductReturn $return, MaterialRequest $request): int
    {
        $composition = collect(data_get($return->scan?->extracted_data, 'composition', []));
        if ($composition->isEmpty()) {
            $composition = $return->product->materials->map(fn ($material) => [
                'material' => $material->name,
                'percentage' => $material->pivot->percentage,
            ]);
        }

        $analysis = $this->compatibility->evaluate($return, $request, $composition);
        $match = MaterialMatch::firstOrNew([
            'material_request_id' => $request->id,
            'product_return_id' => $return->id,
        ]);
        $previousVerdict = $match->ai_verdict;
        $passive = ! $match->exists || in_array($match->status, ['suggested', 'review_needed', 'incompatible'], true);

        $match->fill([
            'match_score' => data_get($analysis, 'details.score', 0),
            'ai_verdict' => $analysis['verdict'],
            'ai_confidence' => $analysis['confidence'],
            'ai_reason' => $analysis['reason'],
            'ai_details' => $analysis['details'],
            'ai_source' => $analysis['source'],
            'ai_analyzed_at' => now(),
        ]);
        if ($passive) {
            $match->status = match ($analysis['verdict']) {
                'compatible' => 'suggested',
                'partial' => 'review_needed',
                default => 'incompatible',
            };
        }
        $match->save();

        if ($analysis['verdict'] !== 'compatible' || $previousVerdict === 'compatible') {
            return 0;
        }

        BrandNotification::create([
            'user_id' => $request->brand->user_id,
            'type' => 'material_match',
            'title' => 'Nouvelle matière compatible',
            'message' => $return->reference.' correspond à votre demande « '.$request->title.' » (score IA '.$match->match_score.' %).',
            'url' => route('brand.material-matches.index'),
        ]);

        return 1;
    }
}
