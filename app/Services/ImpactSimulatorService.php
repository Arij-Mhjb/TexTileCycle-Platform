<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

/**
 * Simulateur « et si ? » : recalcule l'impact d'un produit avec une autre composition,
 * un autre poids ou d'autres caractéristiques circulaires. Même formule que le rapport
 * officiel (ImpactCalculationService::compute) ; rien n'est enregistré.
 */
class ImpactSimulatorService
{
    public function __construct(private ImpactCalculationService $impact) {}

    /**
     * @param  array<int, array{material_id: int, percentage: float}>  $composition
     * @param  array{repairable?: bool, reusable?: bool, recyclable?: bool}  $flags
     * @return array{score: int, grade: string, baseline: int, delta: int, bonus: int, totals: array<string, float>, per_kg: array<string, float>, composition_total: float}
     */
    public function simulate(Product $product, float $reportWeight, float $weight, array $composition, array $flags): array
    {
        // Point de départ recalculé avec les facteurs actuels : la différence ne vient que de la simulation.
        $baseline = $this->impact->compute($product, $reportWeight)['score'];
        $materials = $this->materials($composition);
        $result = $this->impact->compute($product, $weight, $materials, $flags);
        $totals = collect($result['totals'])->map(fn ($value) => round((float) $value, 2))->all();

        return [
            'score' => $result['score'],
            'grade' => $this->impact->grade($result['score']),
            'baseline' => $baseline,
            'delta' => $result['score'] - $baseline,
            'bonus' => $result['bonus'],
            'totals' => $totals,
            'per_kg' => collect($totals)->map(fn ($value) => round($value / max(0.05, $weight), 2))->all(),
            'composition_total' => round(array_sum(array_column($composition, 'percentage')), 1),
        ];
    }

    /** Matières avec leur pourcentage, au format attendu par compute() (->pivot->percentage). */
    private function materials(array $composition): Collection
    {
        $models = Material::whereIn('id', array_column($composition, 'material_id'))->get()->keyBy('id');

        return collect($composition)
            ->filter(fn ($row) => $models->has($row['material_id']) && (float) $row['percentage'] > 0)
            ->map(function ($row) use ($models) {
                $material = clone $models[$row['material_id']];
                $material->setRelation('pivot', new Pivot(['percentage' => (float) $row['percentage']]));

                return $material;
            })
            ->values();
    }
}
