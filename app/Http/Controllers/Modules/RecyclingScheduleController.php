<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\RecyclingLoop;
use App\Models\RecyclingSchedule;
use App\Services\RecyclingScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Collectes programmées (marque) : une demande de recyclage part toute seule chaque mois ou chaque trimestre. */
class RecyclingScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return view('modules.recycling.schedules', [
            'schedules' => RecyclingSchedule::with('supplier')->withCount('loops')->where('brand_id', $brand->id)->latest()->get(),
            'suppliers' => $brand->suppliers()->whereNotNull('user_id')->orderBy('name')->get(),
            'materials' => Material::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $brand = $request->user()->brand()->firstOrFail();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('brand_id', $brand->id)->whereNotNull('user_id')],
            'mode' => ['required', Rule::in(array_keys(RecyclingLoop::MODES))],
            'frequency' => ['required', Rule::in(array_keys(RecyclingSchedule::FREQUENCIES))],
            'day_of_month' => 'required|integer|min:1|max:28',
            'kind' => ['required', Rule::in(['surplus', 'offcuts'])],
            'material_id' => 'required|exists:materials,id',
            'quantity_kg' => 'required|numeric|min:1|max:100000',
            'brand_message' => 'nullable|string|max:500',
        ], [], ['supplier_id' => 'fournisseur', 'day_of_month' => 'jour', 'material_id' => 'matière', 'quantity_kg' => 'quantité', 'kind' => 'type de rebuts']);

        RecyclingSchedule::create([
            'brand_id' => $brand->id, 'supplier_id' => $data['supplier_id'], 'created_by' => $request->user()->id,
            'mode' => $data['mode'], 'frequency' => $data['frequency'], 'day_of_month' => $data['day_of_month'],
            'items' => [['kind' => $data['kind'], 'material_id' => (int) $data['material_id'], 'quantity_kg' => (float) $data['quantity_kg']]],
            'brand_message' => $data['brand_message'] ?? null,
            'next_run_on' => RecyclingSchedule::firstRun((int) $data['day_of_month']),
        ]);

        return back()->with('success', 'Collecte programmée. La première demande partira automatiquement à la date prévue.');
    }

    public function toggle(Request $request, RecyclingSchedule $recyclingSchedule): RedirectResponse
    {
        $this->authorizeSchedule($request, $recyclingSchedule);
        $recyclingSchedule->update(['active' => ! $recyclingSchedule->active]);

        return back()->with('success', $recyclingSchedule->active ? 'Collecte réactivée.' : 'Collecte mise en pause.');
    }

    public function run(Request $request, RecyclingSchedule $recyclingSchedule, RecyclingScheduleService $schedules): RedirectResponse
    {
        $this->authorizeSchedule($request, $recyclingSchedule);
        $loop = $schedules->run($recyclingSchedule, $request->user());

        return to_route('brand.recycling.show', $loop)->with('success', 'Collecte lancée : demande '.$loop->reference.' envoyée.');
    }

    public function destroy(Request $request, RecyclingSchedule $recyclingSchedule): RedirectResponse
    {
        $this->authorizeSchedule($request, $recyclingSchedule);
        $recyclingSchedule->delete();

        return back()->with('success', 'Collecte programmée supprimée.');
    }

    private function authorizeSchedule(Request $request, RecyclingSchedule $schedule): void
    {
        abort_unless($schedule->brand_id === $request->user()->brand?->id, 403);
    }
}
