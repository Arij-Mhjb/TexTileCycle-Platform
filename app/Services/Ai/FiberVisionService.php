<?php

namespace App\Services\Ai;

use App\Models\Material;
use App\Services\RecyclingEstimator;

/**
 * Reconnaît la fibre d'un textile sur une photo avec CLIP (Hugging Face, classification d'image zero-shot)
 * et propose la matière correspondante du référentiel et sa filière de recyclage.
 */
class FiberVisionService
{
    /** Libellé montré à CLIP => [nom affiché, matière du référentiel]. */
    public const FIBERS = [
        'a close-up photo of plain cotton fabric' => ['Coton', 'Coton conventionnel'],
        'a close-up photo of blue denim jeans fabric' => ['Denim (coton)', 'Coton conventionnel'],
        'a close-up photo of shiny polyester sportswear fabric' => ['Polyester', 'Polyester vierge'],
        'a close-up photo of a wool knit sweater' => ['Laine', 'Laine mérinos'],
        'a close-up photo of linen fabric' => ['Lin', 'Lin européen'],
        'a close-up photo of nylon fabric' => ['Nylon / polyamide', 'Polyamide / Nylon'],
        'a close-up photo of silk fabric' => ['Soie', 'Soie'],
        'a close-up photo of leather' => ['Cuir', 'Cuir bovin'],
        'a close-up photo of fleece fabric' => ['Polaire (polyester)', 'Polyester vierge'],
    ];

    public function __construct(private HuggingFaceClient $huggingFace, private RecyclingEstimator $estimator) {}

    /**
     * @return array{available: bool, source: string, model: ?string, matches: array<int, array{fiber: string, material_id: ?int, material: string, confidence: int, route: string, yield: int}>}
     */
    public function identify(string $imagePath): array
    {
        $scores = $this->huggingFace->classifyImage($imagePath, array_keys(self::FIBERS));
        if (! $scores) {
            return ['available' => false, 'source' => 'none', 'model' => null, 'matches' => []];
        }

        // Plusieurs libellés peuvent mener à la même matière (coton, denim) : on additionne.
        $byMaterial = [];
        foreach ($scores as $label => $score) {
            [$fiber, $material] = self::FIBERS[$label] ?? [null, null];
            if ($material) {
                $byMaterial[$material] ??= ['fiber' => $fiber, 'score' => 0.0];
                $byMaterial[$material]['score'] += $score;
            }
        }
        uasort($byMaterial, fn ($a, $b) => $b['score'] <=> $a['score']);
        $ids = Material::whereIn('name', array_keys($byMaterial))->pluck('id', 'name');

        $matches = [];
        foreach (array_slice($byMaterial, 0, 3, true) as $material => $row) {
            [$route, $yield] = $this->estimator->route($material);
            $matches[] = [
                'fiber' => $row['fiber'], 'material_id' => $ids[$material] ?? null, 'material' => $material,
                'confidence' => (int) round($row['score'] * 100), 'route' => $route, 'yield' => (int) round($yield * 100),
            ];
        }

        return ['available' => true, 'source' => 'huggingface', 'model' => config('huggingface.vision_model'), 'matches' => $matches];
    }
}
