<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepairWorkshopRequest;
use App\Http\Requests\UpdateRepairWorkshopRequest;
use App\Models\RepairPartner;
use App\Models\RepairWorkshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RepairWorkshopController extends Controller
{
    public function index(): View
    {
        $items = RepairWorkshop::with('partner')->latest()->paginate(10);

        return view('admin.repair-workshops.index', compact('items'));
    }

    public function catalog(): View
    {
        $items = RepairWorkshop::with('partner')
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return view('repair-workshops.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.repair-workshops.create', [
            'item' => new RepairWorkshop,
            'partners' => RepairPartner::orderBy('name')->get(),
        ]);
    }

    public function store(StoreRepairWorkshopRequest $request): RedirectResponse
    {
        RepairWorkshop::create($request->validated());

        return to_route('admin.repair-workshops.index')->with('success', 'Atelier ajouté.');
    }

    public function show(RepairWorkshop $repairWorkshop): View
    {
        $repairWorkshop->load('partner');

        return view('admin.repair-workshops.show', ['item' => $repairWorkshop]);
    }

    public function showPublic(RepairWorkshop $repairWorkshop): View
    {
        $repairWorkshop->load('partner');

        return view('repair-workshops.show', ['workshop' => $repairWorkshop]);
    }

    public function edit(RepairWorkshop $repairWorkshop): View
    {
        return view('admin.repair-workshops.edit', [
            'item' => $repairWorkshop,
            'partners' => RepairPartner::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRepairWorkshopRequest $request, RepairWorkshop $repairWorkshop): RedirectResponse
    {
        $repairWorkshop->update($request->validated());

        return to_route('admin.repair-workshops.index')->with('success', 'Atelier mis à jour.');
    }

    public function destroy(RepairWorkshop $repairWorkshop): RedirectResponse
    {
        $repairWorkshop->delete();

        return back()->with('success', 'Atelier supprimé.');
    }
}
