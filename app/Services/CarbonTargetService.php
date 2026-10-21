<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CarbonTarget;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImpactReport;
use Illuminate\Support\Collection;

/**
 * Objectifs carbone : on additionne l'empreinte de chaque produit de la collection (CO₂e par unité × unités prévues),
 * on la compare au budget et au plafond par unité, puis on calcule les leviers : passer les matières vierges
 * en recyclé, avec la même formule que le rapport d'impact (simulateur).
 */
class CarbonTargetService
{
    /** Matière vierge => équivalent recyclé (pour chiffrer les leviers). */
    public const RECYCLED_SWAP = [
        'Coton conventionnel' => 'Coton recyclé', 'Coton biologique' => 'Coton recyclé', 'Polyester vierge' => 'Polyester recyclé',
        'Laine mérinos' => 'Laine recyclée', 'Polyamide / Nylon' => 'Polyamide recyclé',
    ];

    public function __construct(private ImpactSimulatorService $simulator) {}

    /** Dernier rapport d'impact de chaque produit de la marque (filtré par saison si l'objectif en a une). */
    public function reports(Brand $brand, ?string $season = null): Collection
    {
        return ProductImpactReport::with('product.materials')
            ->where(fn ($query) => $query->where('brand_id', $brand->id)->orWhereHas('product', fn ($product) => $product->where('brand_id', $brand->id)))
            ->when($season, fn ($query) => $query->where('season', $season))
            ->latest('id')->get()->unique('product_id')->values();
    }

    /** @return array<int, string> saisons connues dans les rapports de la marque */
    public function seasons(Brand $brand): array
    {
        return $this->reports($brand)->pluck('season')->filter()->unique()->sort()->values()->all();
    }

    public function status(CarbonTarget $target): array
    {
        $units = collect($target->planned_units ?? [])->map(fn ($value) => (int) $value);
        $rows = $this->reports($target->brand, $target->season)->map(function (ProductImpactReport $report) use ($units, $target) {
            $planned = $units[$report->product_id] ?? 0;
            $unit = (float) $report->carbon_kg;

            return [
                'product' => $report->product,
                'report' => $report,
                'unit_kg' => round($unit, 2),
                'units' => $planned,
                'total_kg' => round($unit * $planned, 1),
                'over_unit' => $target->max_unit_kg !== null && $unit > (float) $target->max_unit_kg,
                'lever' => $this->lever($report, $planned),
            ];
        })->sortByDesc('total_kg')->values();

        $total = round($rows->sum('total_kg'), 1);
        $budget = $target->budget_kg !== null ? (float) $target->budget_kg : null;
        $usage = $budget ? (int) round($total / $budget * 100) : null;
        $savings = round($rows->sum(fn ($row) => $row['lever']['saving_kg'] ?? 0), 1);
        $state = match (true) {
            $usage !== null && $usage > 100, $rows->contains('over_unit', true) => 'over',
            $usage !== null && $usage >= 90 => 'warning',
            default => 'ok',
        };

        return [
            'rows' => $rows,
            'total_kg' => $total,
            'budget_kg' => $budget,
            'usage' => $usage,
            'remaining_kg' => $budget !== null ? round($budget - $total, 1) : null,
            'over_unit' => $rows->where('over_unit', true)->count(),
            'units' => $rows->sum('units'),
            'savings_kg' => $savings,
            'after_levers_kg' => round($total - $savings, 1),
            'after_levers_usage' => $budget ? (int) round(($total - $savings) / $budget * 100) : null,
            'state' => $state,
            'state_label' => ['over' => 'Dépassé', 'warning' => 'Limite proche', 'ok' => 'Dans le budget'][$state],
        ];
    }

    /** Levier chiffré : remplacer les matières vierges du produit par leur équivalent recyclé. */
    private function lever(ProductImpactReport $report, int $units): ?array
    {
        $product = $report->product;
        if (! $product instanceof Product || $product->materials->isEmpty()) {
            return null;
        }
        $swaps = $product->materials->filter(fn (Material $material) => isset(self::RECYCLED_SWAP[$material->name]));
        if ($swaps->isEmpty()) {
            return null;
        }
        $recycled = Material::whereIn('name', array_values(self::RECYCLED_SWAP))->pluck('id', 'name');
        $share = fn (Material $material) => (float) ($material->pivot->percentage ?? 100 / max(1, $product->materials->count()));
        $current = $product->materials->map(fn (Material $material) => ['material_id' => $material->id, 'percentage' => $share($material)])->values()->all();
        $swapped = $product->materials->map(fn (Material $material) => [
            'material_id' => $recycled[self::RECYCLED_SWAP[$material->name] ?? ''] ?? $material->id,
            'percentage' => $share($material),
        ])->values()->all();

        // Même formule, deux compositions : la différence ne vient que du passage au recyclé.
        $weight = (float) ($report->product_weight_kg ?: 0.5);
        $flags = ['repairable' => (bool) $product->repairable, 'reusable' => (bool) $product->reusable, 'recyclable' => (bool) $product->recyclable];
        $before = (float) $this->simulator->simulate($product, $weight, $weight, $current, $flags)['totals']['co2'];
        $after = (float) $this->simulator->simulate($product, $weight, $weight, $swapped, $flags)['totals']['co2'];
        $saving = max(0, $before - $after);
        if ($saving < 0.01) {
            return null;
        }
        $newUnit = max(0, (float) $report->carbon_kg - $saving);

        return [
            'text' => $swaps->map(fn ($material) => $material->name.' → '.self::RECYCLED_SWAP[$material->name])->join(', '),
            'unit_kg' => round($newUnit, 2),
            'saving_unit_kg' => round($saving, 2),
            'saving_kg' => round($saving * $units, 1),
            'percent' => (int) round($saving / max(0.001, (float) $report->carbon_kg) * 100),
        ];
    }
}
