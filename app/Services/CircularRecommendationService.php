<?php

namespace App\Services;

use App\Models\Product;

class CircularRecommendationService
{
    public function recommend(Product $product, ?string $condition = null): array
    {
        $condition ??= 'unknown';

        if (in_array($condition, ['damaged', 'minor_damage'], true) && $product->repairable) {
            return $this->result('repair', 'Réparer en priorité', 'Ce produit est réparable. Prolonger sa durée de vie évite un traitement prématuré.', $product);
        }

        if (in_array($condition, ['good', 'wearable'], true) && $product->reusable) {
            return $this->result('donation', 'Donner ou réemployer', 'Le produit est encore utilisable. Le don ou le réemploi conserve davantage de valeur que le recyclage.', $product);
        }

        if ($product->recyclable) {
            return $this->result('recycling', 'Recycler dans une filière adaptée', 'La composition déclarée permet d’orienter ce textile vers un programme de recyclage compatible.', $product);
        }

        return $this->result('take_back', 'Retourner à la marque', 'Aucune filière standard n’est confirmée. Une reprise par la marque est l’option la plus sûre.', $product);
    }

    private function result(string $action, string $title, string $explanation, Product $product): array
    {
        $alternatives = collect([
            $product->repairable ? 'repair' : null,
            $product->reusable ? 'donation' : null,
            $product->recyclable ? 'recycling' : null,
            'take_back',
        ])->filter()->unique()->reject(fn ($value) => $value === $action)->values()->all();

        return compact('action', 'title', 'explanation', 'alternatives');
    }
}
