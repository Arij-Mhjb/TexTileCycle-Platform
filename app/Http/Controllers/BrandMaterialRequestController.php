<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Models\RoadmapStep;
use App\Services\MaterialMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandMaterialRequestController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return view('brand.material-requests', [
            'brand' => $brand,
            'requests' => $brand->materialRequests()->with('roadmapStep.roadmap.label')->withCount('matches')->latest()->paginate(12),
            'roadmaps' => $brand->roadmaps()->where('status', 'active')->with('label', 'products', 'steps')->get(),
        ]);
    }

    public function store(Request $request, MaterialMatchingService $matching): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'roadmap_step_id' => 'nullable|integer|exists:roadmap_steps,id',
            'title' => 'required|string|max:255',
            'material_name' => 'required|string|max:255',
            'minimum_percentage' => 'required|numeric|min:0|max:100',
            'categories_text' => 'nullable|string|max:1000',
            'accepted_conditions' => 'required|array|min:1',
            'accepted_conditions.*' => 'in:good,wearable,minor_damage,damaged,unusable,unknown',
            'quantity_kg' => 'required|numeric|min:0.1|max:999999',
            'city' => 'nullable|string|max:255',
            'dropoff_address' => 'nullable|string|max:500',
            'reward_points' => 'required|integer|min:0|max:100000',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $step = ! empty($data['roadmap_step_id']) ? RoadmapStep::findOrFail($data['roadmap_step_id']) : null;
        if ($step) {
            abort_unless($step->roadmap->brand_id === $brand->id, 403);
            abort_unless($step->roadmap->status === 'active' && $step->material_stage === 'collection', 422);
        }
        $materialRequest = $brand->materialRequests()->create([
            'roadmap_scope_version' => $step?->roadmap->scope_version,
            ...collect($data)->except(['categories_text'])->all(),
            'categories' => collect(explode(',', $data['categories_text'] ?? ''))->map(fn ($value) => trim($value))->filter()->values()->all(),
            'remaining_kg' => $data['quantity_kg'],
            'status' => 'open',
        ]);
        $compatible = $matching->matchRequest($materialRequest);

        return redirect()->route('brand.material-matches.index')
            ->with('success', 'Demande publiée et retours existants analysés par l’IA : '.$compatible.' compatible(s).');
    }

    public function destroy(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless($materialRequest->brand_id === $request->user()->brand?->id, 403);
        $materialRequest->update(['status' => 'closed']);

        return back()->with('success', 'Demande clôturée.');
    }
}
