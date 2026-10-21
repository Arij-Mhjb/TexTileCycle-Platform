<?php

namespace App\Services;

use App\Models\RecyclingLoop;
use Illuminate\Support\Collection;

/**
 * Vue admin des flux de recyclage : un diagramme de Sankey Marques → Fournisseurs → Matières recyclées
 * (en kilos, pertes comprises pour que le flux soit conservé), et l'entonnoir des étapes.
 */
class RecyclingFlowService
{
    public const WIDTH = 1000;

    private const COLUMNS = [150, 490, 830];

    private const NODE_WIDTH = 14;

    private const GAP = 14;

    /**
     * @param  Collection<int, RecyclingLoop>  $loops  avec brand, supplier, items, outputMaterial
     * @return array{nodes: array, links: array, height: int, total: float}
     */
    public function sankey(Collection $loops): array
    {
        $links = [];
        foreach ($loops->where('status', '!=', 'declined') as $loop) {
            $input = (float) ($loop->received_kg ?: $loop->totalKg());
            $output = min($input, (float) ($loop->output_kg ?: $loop->expected_output_kg ?: ($loop->pricing['output_kg'] ?? 0)));
            if ($input <= 0) {
                continue;
            }
            $material = $loop->outputMaterial?->name ?? ($loop->pricing['material'] ?? 'Matière recyclée');
            $this->add($links, 'b'.$loop->brand_id, 's'.$loop->supplier_id, $input);
            $this->add($links, 's'.$loop->supplier_id, 'm'.$material, $output);
            $this->add($links, 's'.$loop->supplier_id, 'loss', $input - $output);
        }
        $links = array_values(array_filter($links, fn ($link) => $link['value'] > 0.05));
        if ($links === []) {
            return ['nodes' => [], 'links' => [], 'height' => 0, 'total' => 0.0];
        }

        $labels = $loops->flatMap(fn (RecyclingLoop $loop) => [
            'b'.$loop->brand_id => $loop->brand?->name,
            's'.$loop->supplier_id => $loop->supplier?->name,
            'm'.($loop->outputMaterial?->name ?? ($loop->pricing['material'] ?? 'Matière recyclée')) => $loop->outputMaterial?->name ?? ($loop->pricing['material'] ?? 'Matière recyclée'),
        ])->put('loss', 'Pertes');

        // Valeur de chaque nœud = flux entrant (ou sortant pour la 1re colonne).
        $nodes = [];
        foreach ($links as $link) {
            foreach (['source', 'target'] as $end) {
                $id = $link[$end];
                $nodes[$id] ??= ['id' => $id, 'label' => $labels[$id] ?? $id, 'column' => $this->column($id), 'in' => 0.0, 'out' => 0.0];
            }
            $nodes[$link['source']]['out'] += $link['value'];
            $nodes[$link['target']]['in'] += $link['value'];
        }
        foreach ($nodes as &$node) {
            $node['value'] = max($node['in'], $node['out']);
        }
        unset($node);

        $total = array_sum(array_map(fn ($node) => $node['column'] === 0 ? $node['value'] : 0, $nodes));
        $columns = collect($nodes)->groupBy('column');
        $maxCount = $columns->map->count()->max();
        $height = max(220, 70 * $maxCount);
        $scale = ($height - self::GAP * ($maxCount - 1)) / max($total, 0.001);

        // Placement vertical : chaque colonne est centrée, les nœuds triés du plus lourd au plus léger (pertes en bas).
        foreach ($columns as $column => $items) {
            $sorted = $items->sortByDesc(fn ($node) => $node['id'] === 'loss' ? -1 : $node['value'])->values();
            $used = $sorted->sum(fn ($node) => $node['value'] * $scale) + self::GAP * ($sorted->count() - 1);
            $y = ($height - $used) / 2;
            foreach ($sorted as $node) {
                $h = max(2, $node['value'] * $scale);
                $nodes[$node['id']] += ['x' => self::COLUMNS[$column], 'y' => round($y, 1), 'h' => round($h, 1), 'cursorOut' => $y, 'cursorIn' => $y];
                $y += $h + self::GAP;
            }
        }

        // Liens : rubans de Bézier, empilés dans l'ordre vertical de leur cible.
        usort($links, fn ($a, $b) => [$nodes[$a['source']]['y'], $nodes[$a['target']]['y']] <=> [$nodes[$b['source']]['y'], $nodes[$b['target']]['y']]);
        foreach ($links as &$link) {
            $source = &$nodes[$link['source']];
            $target = &$nodes[$link['target']];
            $thickness = $link['value'] * $scale;
            $y0 = $source['cursorOut'] + $thickness / 2;
            $y1 = $target['cursorIn'] + $thickness / 2;
            $source['cursorOut'] += $thickness;
            $target['cursorIn'] += $thickness;
            $x0 = $source['x'] + self::NODE_WIDTH;
            $x1 = $target['x'];
            $mid = ($x0 + $x1) / 2;
            $link += [
                'path' => sprintf('M%.1f,%.1f C%.1f,%.1f %.1f,%.1f %.1f,%.1f', $x0, $y0, $mid, $y0, $mid, $y1, $x1, $y1),
                'width' => round(max(1.5, $thickness), 1),
                'loss' => $link['target'] === 'loss',
                'label' => $source['label'].' → '.$target['label'],
            ];
            unset($source, $target);
        }
        unset($link);

        return [
            'nodes' => array_values(array_map(fn ($node) => collect($node)->only(['id', 'label', 'column', 'value', 'x', 'y', 'h'])->all(), $nodes)),
            'links' => $links,
            'height' => $height,
            'total' => round($total, 1),
        ];
    }

    /** Combien de demandes sont arrivées à chaque étape (entonnoir). */
    public function funnel(Collection $loops): array
    {
        $order = ['requested', 'proposed', 'accepted', 'in_transit', 'received', 'transformed', 'delivered', 'closed'];
        $rank = fn (RecyclingLoop $loop) => $loop->status === 'declined' ? ($loop->proposed_at ? 1 : 0) : array_search($loop->status, $order, true);
        $steps = [
            ['Demandes envoyées', 0], ['Proposition faite', 1], ['Proposition acceptée', 2], ['Colis reçu', 4], ['Matière produite', 5], ['Terminées', 7],
        ];
        $total = max(1, $loops->count());

        return array_map(fn ($step) => [
            'label' => $step[0],
            'count' => $count = $loops->filter(fn ($loop) => $rank($loop) >= $step[1])->count(),
            'percent' => (int) round($count / $total * 100),
        ], $steps);
    }

    private function add(array &$links, string $source, string $target, float $value): void
    {
        $key = $source.'>'.$target;
        $links[$key] ??= ['source' => $source, 'target' => $target, 'value' => 0.0];
        $links[$key]['value'] += $value;
    }

    private function column(string $id): int
    {
        return match ($id[0]) {
            'b' => 0,
            's' => 1,
            default => 2,
        };
    }
}
