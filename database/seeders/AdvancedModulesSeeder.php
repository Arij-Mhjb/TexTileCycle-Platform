<?php

namespace Database\Seeders;

use App\Models\CarbonTarget;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\RecyclingSchedule;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CarbonTargetService;
use App\Services\CertificateAlertService;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration des métiers avancés : certificats proches de l'expiration (et alertes envoyées),
 * objectif carbone de la collection AH 2026, collecte de chutes programmée chaque mois.
 * Idempotent. Lancer : php artisan db:seed --class=AdvancedModulesSeeder
 */
class AdvancedModulesSeeder extends Seeder
{
    public function run(CarbonTargetService $carbon, CertificateAlertService $alerts): void
    {
        $brandUser = User::where('email', 'brand@textilecycle.test')->first();
        $brand = $brandUser?->brand;
        if (! $brand) {
            return;
        }

        // Certificats : un expiré, un qui expire dans 12 jours, un dans 75 jours.
        foreach (['GRS-2026-4520' => -6, 'RWS-NZ-8812' => 12, 'GOTS-1467-1709' => 75] as $number => $days) {
            MaterialOriginCertificate::where('certificate_number', $number)->update(['expires_at' => now()->addDays($days)->toDateString()]);
        }
        $alerts->run($brand);

        // Objectif carbone de la collection AH 2026 (dépassé, mais tenable avec les leviers).
        if (! CarbonTarget::where('brand_id', $brand->id)->exists()) {
            $season = in_array('AH 2026', $carbon->seasons($brand), true) ? 'AH 2026' : null;
            $units = [];
            foreach ($carbon->reports($brand, $season) as $index => $report) {
                $units[$report->product_id] = [400, 600, 300, 150, 200, 250][$index] ?? 100;
            }
            CarbonTarget::create([
                'brand_id' => $brand->id, 'created_by' => $brandUser->id, 'name' => 'Collection AH 2026 bas carbone',
                'season' => $season, 'budget_kg' => 12000, 'max_unit_kg' => 20, 'planned_units' => $units, 'deadline' => now()->addMonths(3)->toDateString(),
            ]);
        }

        // Collecte programmée : 40 kg de chutes de coton chaque mois chez le fournisseur de démonstration.
        $supplier = Supplier::where('brand_id', $brand->id)->whereNotNull('user_id')->orderBy('id')->first();
        $cotton = Material::where('name', 'Coton conventionnel')->value('id');
        if ($supplier && $cotton && ! RecyclingSchedule::where('brand_id', $brand->id)->exists()) {
            RecyclingSchedule::create([
                'brand_id' => $brand->id, 'supplier_id' => $supplier->id, 'created_by' => $brandUser->id, 'mode' => 'recover',
                'frequency' => 'monthly', 'day_of_month' => 5, 'items' => [['kind' => 'offcuts', 'material_id' => $cotton, 'quantity_kg' => 40]],
                'brand_message' => 'Chutes de l’atelier de coupe.', 'next_run_on' => RecyclingSchedule::firstRun(5), 'runs_count' => 0,
            ]);
        }
    }
}
