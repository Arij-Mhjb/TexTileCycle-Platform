<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandNotification;
use App\Models\ImpactCategory;
use App\Models\ImpactMetric;
use App\Models\Product;
use App\Models\ProductImpactReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Calcul de l'impact environnemental (CO2, eau, énergie, déchets) d'un produit
 * à partir de sa composition, de facteurs par matière et de sa circularité.
 */
class ImpactCalculationService
{
    /** Poids moyen (kg) par catégorie de produit, utilisé si aucun poids n'est saisi. */
    public const DEFAULT_WEIGHTS = [
        't-shirt' => 0.2, 'chemise' => 0.25, 'robe' => 0.35, 'pantalon' => 0.6, 'jean' => 0.7,
        'pull' => 0.5, 'veste' => 1.0, 'manteau' => 1.4, 'chaussure' => 0.9, 'sac' => 0.8,
    ];

    /** Facteurs par kg de matière, par type, quand aucune métrique n'est saisie. */
    public const DEFAULT_FACTORS = [
        'plant' => ['co2' => 5.0, 'water' => 5000, 'energy' => 40, 'waste' => 0.15],
        'animal' => ['co2' => 18.0, 'water' => 2500, 'energy' => 55, 'waste' => 0.2],
        'recycled' => ['co2' => 1.6, 'water' => 300, 'energy' => 15, 'waste' => 0.05],
        'regenerated' => ['co2' => 4.0, 'water' => 700, 'energy' => 60, 'waste' => 0.12],
        'synthetic' => ['co2' => 6.5, 'water' => 60, 'energy' => 95, 'waste' => 0.1],
    ];

    public const FALLBACK_FACTORS = ['co2' => 6.0, 'water' => 2000, 'energy' => 50, 'waste' => 0.12];

    /** Facteurs par kg de produit fini pour les autres étapes du cycle de vie. */
    private const STAGE_FACTORS = [
        'manufacturing' => ['co2' => 4.0, 'water' => 50, 'energy' => 25, 'waste' => 0.12],
        'transport' => ['co2' => 0.8, 'water' => 0, 'energy' => 3, 'waste' => 0],
    ];

    public function estimateWeight(Product $product): float
    {
        $category = Str::lower(Str::ascii((string) $product->category));
        foreach (self::DEFAULT_WEIGHTS as $keyword => $weight) {
            if (Str::contains($category, Str::ascii($keyword))) {
                return $weight;
            }
        }

        return 0.5;
    }

    public function generate(Product $product, ?User $author = null, ?float $weightKg = null, ?string $season = null): ProductImpactReport
    {
        $product->loadMissing(['materials', 'returns']);
        $weight = max(0.05, $weightKg ?: $this->estimateWeight($product));
        $categories = ImpactCategory::all()->keyBy('code');
        $metrics = $this->metricsFor($product->materials->pluck('id'));

        // 1. Étape matières : masse de chaque matière × facteur.
        $materials = ['co2' => 0.0, 'water' => 0.0, 'energy' => 0.0, 'waste' => 0.0];
        $totalShare = max(1, $product->materials->sum(fn ($material) => (float) ($material->pivot->percentage ?? 0)));
        foreach ($product->materials as $material) {
            $share = $material->pivot->percentage !== null ? (float) $material->pivot->percentage / $totalShare : 1 / max(1, $product->materials->count());
            $mass = $weight * $share;
            foreach (array_keys($materials) as $code) {
                $factor = $metrics[$material->id][$code] ?? (self::DEFAULT_FACTORS[$material->type] ?? self::FALLBACK_FACTORS)[$code];
                $materials[$code] += $mass * $factor;
            }
        }
        if ($product->materials->isEmpty()) {
            foreach (self::FALLBACK_FACTORS as $code => $factor) {
                $materials[$code] = $weight * $factor;
            }
        }

        // 2. Fabrication, transport, fin de vie.
        $endOfLife = $product->recyclable
            ? ['co2' => 0.2, 'water' => 0, 'energy' => 2, 'waste' => 0.1]
            : ['co2' => 1.2, 'water' => 0, 'energy' => 1, 'waste' => 0.6];
        $stages = [
            'materials' => $materials,
            'manufacturing' => array_map(fn ($factor) => $factor * $weight, self::STAGE_FACTORS['manufacturing']),
            'transport' => array_map(fn ($factor) => $factor * $weight, self::STAGE_FACTORS['transport']),
            'end_of_life' => array_map(fn ($factor) => $factor * $weight, $endOfLife),
        ];
        $totals = collect(['co2', 'water', 'energy', 'waste'])->mapWithKeys(fn ($code) => [$code => collect($stages)->sum($code)]);

        // 3. Les retours traités évitent des déchets (contribution des ProductReturn).
        $avoided = (float) $product->returns
            ->whereIn('status', ['completed', 'processed', 'brand_received', 'routed'])
            ->sum(fn ($return) => (float) ($return->confirmed_weight_kg ?? $return->weight_kg ?? 0));

        // 4. Score : sous-scores pondérés + bonus circularité.
        $subScores = [
            'co2' => $this->clamp(100 - (($totals['co2'] / $weight) - 3) * 5),
            'water' => $this->clamp(100 - ($totals['water'] / $weight) / 40),
            'energy' => $this->clamp(100 - (($totals['energy'] / $weight) - 20) * 1.2),
            'waste' => $this->clamp(100 - ($totals['waste'] / $weight) * 150),
        ];
        $weights = collect($subScores)->mapWithKeys(fn ($score, $code) => [$code => (float) ($categories[$code]->weight ?? 0.25)]);
        $weighted = 0.0;
        foreach ($subScores as $code => $subScore) {
            $weighted += $subScore * $weights[$code];
        }
        $base = $weighted / max(0.01, $weights->sum());
        $bonus = (int) min(10, ($product->repairable ? 3 : 0) + ($product->reusable ? 2 : 0) + ($product->recyclable ? 2 : 0)
            + round(((int) $product->circularity_score) / 100 * 2) + min(1, (int) floor($avoided)));
        $score = (int) $this->clamp(round($base + $bonus));

        return DB::transaction(function () use ($product, $author, $weight, $season, $stages, $totals, $avoided, $bonus, $score) {
            $report = ProductImpactReport::create([
                'product_id' => $product->id,
                'brand_id' => $product->brand_id,
                'generated_by' => $author?->id,
                'season' => $season,
                'product_weight_kg' => round($weight, 3),
                'carbon_kg' => round($totals['co2'], 3),
                'water_l' => round($totals['water'], 2),
                'energy_kwh' => round($totals['energy'], 3),
                'waste_kg' => round($totals['waste'], 3),
                'avoided_waste_kg' => round($avoided, 3),
                'circularity_bonus' => $bonus,
                'impact_score' => $score,
                'grade' => $this->grade($score),
                'status' => 'draft',
            ]);

            foreach ($stages as $stage => $values) {
                $report->carbonFootprints()->create(['stage' => $stage, 'kg_co2e' => round($values['co2'], 3)]);
                $report->waterFootprints()->create(['stage' => $stage, 'liters' => round($values['water'], 2)]);
            }

            return $report->load(['carbonFootprints', 'waterFootprints']);
        });
    }

    public function validate(ProductImpactReport $report, User $admin, ?int $assessmentId = null, ?string $note = null): ProductImpactReport
    {
        $report->update([
            'status' => 'validated',
            'validated_by' => $admin->id,
            'validated_at' => now(),
            'assessment_id' => $assessmentId,
            'notes' => $note ?: $report->notes,
        ]);

        if ($userId = $report->brand?->user_id) {
            BrandNotification::create([
                'user_id' => $userId,
                'type' => 'impact_report_validated',
                'title' => 'Rapport d’impact validé',
                'message' => $report->product->name.' : score '.$report->impact_score.'/100 (classe '.$report->grade.').',
                'url' => route('brand.impact.show', $report),
            ]);
        }

        return $report;
    }

    /**
     * Tableau de bord ESG d'une marque, calculé à partir des derniers rapports par produit.
     */
    public function brandDashboard(Brand $brand): array
    {
        $reports = ProductImpactReport::with('product')->where('brand_id', $brand->id)->oldest()->get();
        $latest = $reports->groupBy('product_id')->map->last()->values();

        return [
            'latest' => $latest->sortByDesc('impact_score')->values(),
            'averageScore' => (int) round($latest->avg('impact_score') ?? 0),
            'totals' => [
                'carbon' => $latest->sum('carbon_kg'),
                'water' => $latest->sum('water_l'),
                'energy' => $latest->sum('energy_kwh'),
                'waste' => $latest->sum('waste_kg'),
                'avoided' => $latest->sum('avoided_waste_kg'),
            ],
            'grades' => collect(['A', 'B', 'C', 'D', 'E'])->mapWithKeys(fn ($grade) => [$grade => $latest->where('grade', $grade)->count()]),
            'byCategory' => $latest->groupBy(fn ($report) => $report->product?->category ?: 'Autre')
                ->map(fn (Collection $items) => (int) round($items->avg('impact_score'))),
            'trend' => $reports->groupBy(fn ($report) => $report->created_at->format('Y-m'))
                ->map(fn (Collection $items) => (int) round($items->avg('impact_score'))),
            'validated' => $latest->where('status', 'validated')->count(),
        ];
    }

    public function grade(int $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 65 => 'B',
            $score >= 50 => 'C',
            $score >= 35 => 'D',
            default => 'E',
        };
    }

    /** @return array<int, array<string, float>> */
    private function metricsFor(Collection $materialIds): array
    {
        return ImpactMetric::with('category')->whereIn('material_id', $materialIds)->get()
            ->groupBy('material_id')
            ->map(fn (Collection $items) => $items->mapWithKeys(fn ($metric) => [$metric->category->code => (float) $metric->value_per_kg])->all())
            ->all();
    }

    private function clamp(float $value): float
    {
        return max(0, min(100, $value));
    }
}
