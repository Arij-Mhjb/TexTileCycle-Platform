<?php

namespace App\Services;

use App\Models\Material;
use App\Models\RecyclingLoop;
use Illuminate\Support\Str;

/**
 * Estimation de la boucle : quelle matière recyclée peut sortir des articles envoyés,
 * et combien de kilos (rendements moyens de l'effilochage / régénération par famille de fibre).
 */
class RecyclingEstimator
{
    /** Mot-clé de la matière d'origine => [matière recyclée produite, rendement]. */
    private const ROUTES = [
        'coton' => ['Coton recyclé', 0.85],
        'chanvre' => ['Coton recyclé', 0.75],
        'lin' => ['Coton recyclé', 0.75],
        'polyester' => ['Polyester recyclé', 0.90],
        'acrylique' => ['Polyester recyclé', 0.80],
        'polyamide' => ['Polyamide recyclé', 0.88],
        'nylon' => ['Polyamide recyclé', 0.88],
        'laine' => ['Laine recyclée', 0.80],
        'cachemire' => ['Laine recyclée', 0.78],
        'alpaga' => ['Laine recyclée', 0.78],
        'caoutchouc' => ['Caoutchouc recyclé', 0.70],
    ];

    private const FALLBACK = ['Coton recyclé', 0.60];

    /** Bonus de rendement : un surplus propre se recycle mieux qu'une pièce cousue. */
    private const KIND_FACTOR = ['surplus' => 1.0, 'offcuts' => 0.95, 'defective' => 0.85];

    /** Rendement selon la nature des articles (exposé pour l'estimation en direct du formulaire). */
    public function kindFactors(): array
    {
        return self::KIND_FACTOR;
    }

    /**
     * @return array{material: ?Material, material_name: string, expected_kg: float, yield: int, input_kg: float}
     */
    public function estimate(RecyclingLoop $loop): array
    {
        $loop->loadMissing(['items.material', 'items.product.materials']);
        $byOutput = [];
        foreach ($loop->items as $item) {
            [$name, $rate] = $this->route($item->mainMaterial()?->name);
            $kg = (float) $item->quantity_kg;
            $byOutput[$name]['input'] = ($byOutput[$name]['input'] ?? 0) + $kg;
            $byOutput[$name]['output'] = ($byOutput[$name]['output'] ?? 0) + $kg * $rate * (self::KIND_FACTOR[$item->kind] ?? 0.85);
        }

        if ($byOutput === []) {
            return ['material' => null, 'material_name' => self::FALLBACK[0], 'expected_kg' => 0.0, 'yield' => 0, 'input_kg' => 0.0];
        }

        uasort($byOutput, fn ($a, $b) => $b['input'] <=> $a['input']);
        $name = array_key_first($byOutput);
        $input = array_sum(array_column($byOutput, 'input'));
        $output = array_sum(array_column($byOutput, 'output'));

        return [
            'material' => Material::where('name', $name)->first(),
            'material_name' => $name,
            'expected_kg' => round($output, 1),
            'yield' => $input > 0 ? (int) round($output / $input * 100) : 0,
            'input_kg' => round($input, 2),
        ];
    }

    /** @return array{0: string, 1: float} matière recyclée produite et rendement, pour une matière d'origine */
    public function route(?string $materialName): array
    {
        $name = Str::lower(Str::ascii((string) $materialName));
        foreach (self::ROUTES as $keyword => $route) {
            if (Str::contains($name, $keyword)) {
                return $route;
            }
        }

        return self::FALLBACK;
    }
}
