<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\CarbonTarget;
use App\Services\CarbonTargetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Objectifs carbone d'une marque (Impact ESG). */
class CarbonTargetController extends Controller
{
    public function index(Request $request, CarbonTargetService $carbon): View
    {
        $brand = $request->user()->brand()->firstOrFail();
        $targets = CarbonTarget::with('brand')->where('brand_id', $brand->id)->latest()->get();

        return view('modules.esg.targets.index', [
            'brand' => $brand,
            'targets' => $targets->map(fn (CarbonTarget $target) => ['target' => $target, 'status' => $carbon->status($target)]),
            'seasons' => $carbon->seasons($brand),
        ]);
    }

    public function show(Request $request, CarbonTarget $carbonTarget, CarbonTargetService $carbon): View
    {
        $this->authorizeTarget($request, $carbonTarget);

        return view('modules.esg.targets.show', ['target' => $carbonTarget->load('brand'), 'status' => $carbon->status($carbonTarget)]);
    }

    public function store(Request $request, CarbonTargetService $carbon): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $this->validated($request);
        // Unités prévues : 100 par produit par défaut, modifiables ensuite.
        $units = $carbon->reports($brand, $data['season'] ?? null)->mapWithKeys(fn ($report) => [$report->product_id => 100])->all();
        $target = CarbonTarget::create($data + ['brand_id' => $brand->id, 'created_by' => $request->user()->id, 'planned_units' => $units]);

        return to_route('brand.carbon-targets.show', $target)->with('success', 'Objectif créé. Indiquez maintenant combien d’unités vous prévoyez de produire.');
    }

    public function update(Request $request, CarbonTarget $carbonTarget): RedirectResponse
    {
        $this->authorizeTarget($request, $carbonTarget);
        $data = array_intersect_key($this->validated($request, partial: true), $request->only(['name', 'season', 'budget_kg', 'max_unit_kg', 'deadline']));
        $request->validate(['units' => 'nullable|array', 'units.*' => 'nullable|integer|min:0|max:10000000']);
        if ($request->has('units')) {
            $data['planned_units'] = collect($request->input('units'))->map(fn ($value) => (int) $value)->all();
        }
        $carbonTarget->update($data);

        return back()->with('success', 'Objectif mis à jour.');
    }

    public function destroy(Request $request, CarbonTarget $carbonTarget): RedirectResponse
    {
        $this->authorizeTarget($request, $carbonTarget);
        $carbonTarget->delete();

        return to_route('brand.carbon-targets.index')->with('success', 'Objectif supprimé.');
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (string $rules) => ($partial ? 'sometimes|' : '').$rules;

        return $request->validate([
            'name' => $rule('required|string|max:120'),
            'season' => 'nullable|string|max:40',
            'budget_kg' => 'nullable|numeric|min:1|max:100000000'.($partial ? '' : '|required_without:max_unit_kg'),
            'max_unit_kg' => 'nullable|numeric|min:0.01|max:10000',
            'deadline' => 'nullable|date',
        ], ['budget_kg.required_without' => 'Indiquez un budget total ou un plafond par unité.'], ['name' => 'nom', 'budget_kg' => 'budget', 'max_unit_kg' => 'plafond par unité']);
    }

    private function authorizeTarget(Request $request, CarbonTarget $target): void
    {
        abort_unless($target->brand_id === $request->user()->brand?->id, 403);
    }
}
