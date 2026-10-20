<?php

namespace App\Http\Controllers;

use App\Models\RepairPartner;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(): View
    {
        $partners = RepairPartner::query()
            ->withCount('workshops')
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return view('partners.index', [
            'partners' => $partners,
        ]);
    }

    public function show(RepairPartner $repairPartner): View
    {
        $repairPartner->load('workshops');

        return view('partners.show', [
            'partner' => $repairPartner,
        ]);
    }
}
