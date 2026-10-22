<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ProductImpactReport;
use App\Services\ImpactSimulatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImpactSimulatorController extends Controller
{
    public function __invoke(Request $request, ProductImpactReport $report, ImpactSimulatorService $simulator): JsonResponse
    {
        if ($request->user()->role === 'brand') {
            abort_unless($report->brand_id === $request->user()->brand?->id, 403);
        }

        $data = $request->validate([
            'weight_kg' => 'required|numeric|min:0.05|max:50',
            'composition' => 'required|array|min:1|max:8',
            'composition.*.material_id' => 'required|integer|exists:materials,id',
            'composition.*.percentage' => 'required|numeric|min:0|max:100',
            'repairable' => 'boolean',
            'reusable' => 'boolean',
            'recyclable' => 'boolean',
        ]);

        return response()->json($simulator->simulate(
            $report->product,
            (float) $report->product_weight_kg,
            (float) $data['weight_kg'],
            $data['composition'],
            ['repairable' => (bool) ($data['repairable'] ?? false), 'reusable' => (bool) ($data['reusable'] ?? false), 'recyclable' => (bool) ($data['recyclable'] ?? false)],
        ));
    }
}
