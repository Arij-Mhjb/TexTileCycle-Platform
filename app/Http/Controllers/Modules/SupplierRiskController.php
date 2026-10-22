<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\CertificateAlert;
use App\Models\Supplier;
use App\Services\CertificateAlertService;
use App\Services\SupplierRiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Risques fournisseurs et alertes d'expiration des certificats (marque : ses fournisseurs ; admin : tous). */
class SupplierRiskController extends Controller
{
    public function brand(Request $request, SupplierRiskService $risk): View
    {
        $brand = $request->user()->brand()->firstOrFail();

        return $this->page('brand', Supplier::where('brand_id', $brand->id)->get(), $risk);
    }

    public function admin(SupplierRiskService $risk): View
    {
        return $this->page('admin', Supplier::with('brand')->get(), $risk);
    }

    public function sendAlerts(Request $request, CertificateAlertService $alerts): RedirectResponse
    {
        $count = $alerts->run($request->user()->brand()->firstOrFail());

        return back()->with('success', $count ? $count.' alerte(s) envoyée(s) à vous et à vos fournisseurs.' : 'Aucune nouvelle alerte : tout le monde est déjà prévenu.');
    }

    private function page(string $space, Collection $suppliers, SupplierRiskService $risk): View
    {
        $portfolio = $risk->portfolio($suppliers);
        $expiring = $risk->expiringCertificates($suppliers, 120);

        return view('modules.suppliers.risk', [
            'space' => $space,
            'portfolio' => $portfolio,
            'expiring' => $expiring,
            'alerts' => CertificateAlert::with('certificate.batch.supplier')
                ->whereHas('certificate.batch.supplier', fn ($query) => $query->whereIn('suppliers.id', $suppliers->pluck('id')))
                ->latest('sent_at')->take(8)->get(),
            'stats' => [
                'high' => $portfolio->where('level', 'élevé')->count(),
                'average' => (int) round($portfolio->avg('score') ?? 0),
                'expiring30' => $expiring->filter(fn ($row) => $row['days'] >= 0 && $row['days'] <= 30)->count(),
                'expired' => $expiring->filter(fn ($row) => $row['days'] < 0)->count(),
            ],
        ]);
    }
}
