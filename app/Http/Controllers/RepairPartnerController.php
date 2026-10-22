<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepairPartnerRequest;
use App\Http\Requests\UpdateRepairPartnerRequest;
use App\Models\RepairPartner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepairPartnerController extends Controller
{
    public function index(): View
    {
        $items = RepairPartner::withCount('workshops')->latest()->paginate(10);

        return view('admin.repair-partners.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.repair-partners.create', ['item' => new RepairPartner]);
    }

    public function store(StoreRepairPartnerRequest $request): RedirectResponse
    {
        RepairPartner::create($request->validated());

        return to_route('admin.repair-partners.index')->with('success', 'Partenaire circulaire créé.');
    }

    public function show(RepairPartner $repairPartner): View
    {
        $repairPartner->load('workshops');

        return view('admin.repair-partners.show', ['item' => $repairPartner]);
    }

    public function edit(RepairPartner $repairPartner): View
    {
        return view('admin.repair-partners.edit', ['item' => $repairPartner]);
    }

    public function update(UpdateRepairPartnerRequest $request, RepairPartner $repairPartner): RedirectResponse
    {
        $repairPartner->update($request->validated());

        return to_route('admin.repair-partners.index')->with('success', 'Partenaire circulaire mis à jour.');
    }

    public function destroy(RepairPartner $repairPartner): RedirectResponse
    {
        if ($repairPartner->workshops()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce partenaire tant qu\'il a des ateliers associés.');
        }

        $repairPartner->delete();

        return back()->with('success', 'Partenaire circulaire supprimé.');
    }
}
