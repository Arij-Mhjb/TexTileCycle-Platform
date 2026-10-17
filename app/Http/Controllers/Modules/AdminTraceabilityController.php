<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ProductSupplierLink;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTraceabilityController extends Controller
{
    public function index(Request $request): View
    {
        $links = ProductSupplierLink::with(['product.brandProfile', 'batch.supplier', 'batch.material'])
            ->when($request->filled('conformity'), fn ($query) => $query->where('conformity_status', $request->conformity))
            ->latest()->paginate(15)->withQueryString();

        return view('modules.suppliers.admin.traceability', [
            'links' => $links,
            'counts' => ProductSupplierLink::selectRaw('conformity_status, count(*) as total')->groupBy('conformity_status')->pluck('total', 'conformity_status'),
        ]);
    }

    public function check(ProductSupplierLink $link, SupplierTraceabilityService $traceability): RedirectResponse
    {
        $issues = $traceability->checkConformity($link);

        return back()->with('success', $issues === [] ? 'Matière conforme aux déclarations du produit.' : count($issues).' écart(s) détecté(s) pour '.$link->product->name.'.');
    }
}
