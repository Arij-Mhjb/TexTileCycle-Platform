<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\MaterialMatch;
use App\Models\MaterialRequest;
use App\Models\ProductReturn;

class MaterialMatchingService
{
    public function match(ProductReturn $return): int
    {
        $return->loadMissing(['scan', 'product.materials']);
        $composition = collect(data_get($return->scan?->extracted_data, 'composition', []));
        if ($composition->isEmpty()) {
            $composition = $return->product->materials->map(fn ($material) => [
                'material' => $material->name,
                'percentage' => $material->pivot->percentage,
            ]);
        }

        $created = 0;

        MaterialRequest::query()
            ->where('status', 'open')
            ->where('remaining_kg', '>', 0)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('brand.user')
            ->get()
            ->each(function (MaterialRequest $request) use ($return, $composition, &$created) {
                $material = $composition->first(fn ($item) =>
                    str_contains(mb_strtolower((string) data_get($item, 'material')), mb_strtolower($request->material_name))
                    || str_contains(mb_strtolower($request->material_name), mb_strtolower((string) data_get($item, 'material')))
                );

                if (! $material || (float) data_get($material, 'percentage', 0) < (float) $request->minimum_percentage) {
                    return;
                }

                if ($request->categories && ! in_array($return->product->category, $request->categories, true)) {
                    return;
                }

                if ($request->accepted_conditions && ! in_array($return->condition, $request->accepted_conditions, true)) {
                    return;
                }

                $purity = min(100, (float) data_get($material, 'percentage', 0));
                $score = round(($purity * 0.7) + 30, 2);

                $match = MaterialMatch::firstOrCreate(
                    ['material_request_id' => $request->id, 'product_return_id' => $return->id],
                    ['match_score' => $score, 'status' => 'suggested']
                );

                if ($match->wasRecentlyCreated) {
                    $created++;
                    BrandNotification::create([
                        'user_id' => $request->brand->user_id,
                        'type' => 'material_match',
                        'title' => 'Nouvelle matière compatible',
                        'message' => $return->reference.' correspond à votre demande « '.$request->title.' ».',
                        'url' => route('brand.material-matches.index'),
                    ]);
                }
            });

        return $created;
    }
}
