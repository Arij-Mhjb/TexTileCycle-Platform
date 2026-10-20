<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\RepairCenter;
use App\Models\RepairOrder;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRepairOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = RepairOrder::with(['consumer', 'product', 'repairCenter', 'productReturn'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()->paginate(15)->withQueryString();

        return view('modules.repair.admin.orders.index', [
            'orders' => $orders,
            'counts' => RepairOrder::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            // Retours en bon état, pas encore orientés vers la seconde vie.
            'eligibleReturns' => ProductReturn::with(['product', 'user'])
                ->whereIn('condition', ['good', 'wearable', 'minor_damage'])
                ->whereNotIn('status', ['declared', 'cancelled', 'rejected'])
                ->whereDoesntHave('repairOrders')
                ->latest()->take(6)->get(),
        ]);
    }

    public function store(Request $request, ProductReturn $productReturn, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['repair', 'resale'])]]);
        $order = $service->createFromReturn($productReturn, $data['decision']);

        return to_route('admin.repair-orders.show', $order)->with('success', 'Retour '.$productReturn->reference.' orienté vers la seconde vie.');
    }

    public function show(RepairOrder $repairOrder): View
    {
        $repairOrder->load(['consumer', 'product.brandProfile', 'repairCenter', 'productReturn', 'refurbishments', 'listing.offers']);

        return view('modules.repair.admin.orders.show', [
            'order' => $repairOrder,
            'centers' => RepairCenter::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function diagnose(Request $request, RepairOrder $repairOrder, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(array_keys(RepairOrder::DECISIONS))],
            'repair_center_id' => ['nullable', 'required_unless:decision,recycle', Rule::exists('repair_centers', 'id')->where('is_active', true)],
            'estimated_cost' => 'nullable|numeric|min:0|max:100000',
            'admin_note' => 'nullable|string|max:1000',
        ]);
        $service->diagnose($repairOrder, $data);

        return back()->with('success', 'Diagnostic enregistré : '.$repairOrder->decisionLabel().'.');
    }

    public function start(RepairOrder $repairOrder, RepairMarketplaceService $service): RedirectResponse
    {
        $service->startRepair($repairOrder);

        return back()->with('success', 'Article envoyé au centre '.$repairOrder->repairCenter->name.'.');
    }

    public function refurbish(Request $request, RepairOrder $repairOrder, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'actions' => 'required|string|max:2000',
            'grade' => 'required|in:A,B,C',
            'cost' => 'nullable|numeric|min:0|max:100000',
            'technician' => 'nullable|string|max:120',
        ]);
        $service->refurbish($repairOrder, $data);

        return back()->with('success', 'Reconditionnement enregistré (grade '.$data['grade'].').');
    }

    public function publish(Request $request, RepairOrder $repairOrder, RepairMarketplaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:1|max:100000',
        ]);
        $listing = $service->publish($repairOrder, $data);

        return to_route('admin.marketplace.show', $listing)->with('success', 'Annonce publiée et consommateur récompensé.');
    }

    public function close(RepairOrder $repairOrder, RepairMarketplaceService $service): RedirectResponse
    {
        $service->closeRepair($repairOrder);

        return back()->with('success', 'Réparation clôturée et points crédités.');
    }
}
