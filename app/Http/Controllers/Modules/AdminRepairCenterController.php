<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\RepairCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRepairCenterController extends Controller
{
    public function index(): View
    {
        return view('modules.repair.admin.centers.index', [
            'centers' => RepairCenter::withCount(['repairOrders', 'repairOrders as active_orders_count' => fn ($query) => $query->whereIn('status', ['diagnosed', 'in_repair'])])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('modules.repair.admin.centers.form', ['center' => new RepairCenter(['is_active' => true, 'capacity_per_week' => 10])]);
    }

    public function store(Request $request): RedirectResponse
    {
        RepairCenter::create($this->validated($request));

        return to_route('admin.repair-centers.index')->with('success', 'Centre de réparation ajouté.');
    }

    public function edit(RepairCenter $repairCenter): View
    {
        return view('modules.repair.admin.centers.form', ['center' => $repairCenter]);
    }

    public function update(Request $request, RepairCenter $repairCenter): RedirectResponse
    {
        $repairCenter->update($this->validated($request));

        return to_route('admin.repair-centers.index')->with('success', 'Centre de réparation mis à jour.');
    }

    public function destroy(RepairCenter $repairCenter): RedirectResponse
    {
        $repairCenter->delete();

        return back()->with('success', 'Centre supprimé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'specialties' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:40',
            'capacity_per_week' => 'required|integer|min:1|max:1000',
            'is_active' => 'boolean',
        ]);
    }
}
