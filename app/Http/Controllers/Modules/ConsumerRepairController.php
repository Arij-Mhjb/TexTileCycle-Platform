<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\RepairOrder;
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
            'returns' => $user->productReturns()->with('product')->whereNotIn('status', ['cancelled', 'rejected'])->whereDoesntHave('repairOrders')->latest()->get(),
        ]);
    }

    public function store(Request $request, RepairMarketplaceService $service, RepairAiService $ai): RedirectResponse
    {
        $data = $request->validate([
            'product_return_id' => ['required', Rule::exists('product_returns', 'id')->where('user_id', $request->user()->id)],
            'issue_description' => 'required|string|min:10|max:2000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);
        $order = $service->requestRepair($request->user(), $data);
        $diagnosis = $ai->diagnose($order, $request->file('photo')?->getRealPath());

        return back()->with('success', 'Demande '.$order->reference.' envoyée. Pré-diagnostic IA : '.(RepairOrder::DECISIONS[$diagnosis['decision']] ?? $diagnosis['decision']).'.');
    }
}
