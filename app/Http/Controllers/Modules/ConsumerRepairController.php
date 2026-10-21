<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\RepairOrder;
use App\Models\RepairCenter;
use App\Services\Ai\RepairAiService;
use App\Services\RepairMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsumerRepairController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('modules.repair.consumer.repairs', [
            'orders' => $user->repairOrders()->with(['product', 'repairCenter', 'refurbishments', 'listing'])->latest()->get(),
            'returns' => $user->productReturns()->with('product')->whereNotIn('status', ['cancelled', 'rejected'])->whereDoesntHave('repairOrders', fn ($query) => $query->where('status', '!=', 'cancelled'))->latest()->get(),
            'centers' => RepairCenter::where('is_active', true)->orderBy('city')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, RepairMarketplaceService $service, RepairAiService $ai): RedirectResponse
    {
        $data = $request->validate([
            'product_return_id' => [
                'required',
                Rule::exists('product_returns', 'id')->where('user_id', $request->user()->id),
                // Un retour = une seule demande de réparation.
                Rule::unique('repair_orders', 'product_return_id')->where(fn ($query) => $query->where('status', '!=', 'cancelled')),
            ],
            'issue_description' => 'required|string|min:10|max:2000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'repair_center_id' => 'nullable|exists:repair_centers,id',
            'preferred_appointment_at' => 'nullable|date|after:now',
        ], ['product_return_id.unique' => 'Une demande de réparation existe déjà pour cet article.'], ['product_return_id' => 'article retourné']);
        $order = $service->requestRepair($request->user(), $data);
        $diagnosis = $ai->diagnose($order, $request->file('photo')?->getRealPath());

        return back()->with('success', 'Demande '.$order->reference.' envoyée. Pré-diagnostic IA : '.(RepairOrder::DECISIONS[$diagnosis['decision']] ?? $diagnosis['decision']).'.');
    }

    public function respond(Request $request, RepairOrder $repairOrder, RepairMarketplaceService $service): RedirectResponse
    {
        abort_unless($repairOrder->user_id === $request->user()->id, 403);
        $data = $request->validate(['decision' => 'required|in:accept,decline']);
        $service->consumerDecision($repairOrder, $data['decision'] === 'accept');

        return back()->with('success', $data['decision'] === 'accept'
            ? 'Devis et orientation acceptés. Le centre peut démarrer la prise en charge.'
            : 'Proposition refusée. La demande est annulée.');
    }
}
