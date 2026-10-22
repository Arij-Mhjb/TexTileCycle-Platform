<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\SupplierScorecardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierScorecardController extends Controller
{
    public function brand(Request $request, SupplierScorecardService $scorecard): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return $this->render('brand', $scorecard->ranking($brand->suppliers()->with('brand')->get()));
    }

    public function admin(SupplierScorecardService $scorecard): View
    {
        return $this->render('admin', $scorecard->ranking(Supplier::with('brand')->get()));
    }

    private function render(string $space, $cards): View
    {
        $rated = $cards->filter(fn ($card) => $card['score'] !== null);

        return view('modules.suppliers.performance', [
            'space' => $space,
            'cards' => $cards,
            'stats' => [
                'otif' => $rated->whereNotNull('otif')->avg('otif'),
                'fill' => $rated->whereNotNull('fill_rate')->avg('fill_rate'),
                'late' => $cards->sum('open_late'),
                'best' => $rated->first()['supplier']->name ?? '—',
            ],
        ]);
    }
}
