<?php

/*
| Fonctionnalités ajoutées aux modules d'Ons : boucle de recyclage marque ↔ fournisseur (seconde vie de la matière),
| simulateur ESG, performance (OTIF) et risque fournisseurs, objectifs carbone, anti-greenwashing, copilote IA,
| collectes programmées et factures / avoirs PDF.
| Chargées par App\Providers\CircularModulesServiceProvider (middleware "web").
*/

use App\Http\Controllers\Modules\CarbonTargetController;
use App\Http\Controllers\Modules\CopilotController;
use App\Http\Controllers\Modules\GreenClaimsController;
use App\Http\Controllers\Modules\ImpactSimulatorController;
use App\Http\Controllers\Modules\RecyclingLoopController;
use App\Http\Controllers\Modules\RecyclingScheduleController;
use App\Http\Controllers\Modules\SupplierRiskController;
use App\Http\Controllers\Modules\SupplierScorecardController;
use App\Http\Middleware\EnsureSupplierPasswordChanged;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function () {
        // Seconde vie : boucle de recyclage avec le fournisseur
        Route::get('/recyclage', [RecyclingLoopController::class, 'brandIndex'])->name('recycling.index');
        Route::get('/recyclage/nouveau', [RecyclingLoopController::class, 'create'])->name('recycling.create');
        Route::post('/recyclage/reconnaitre-matiere', [RecyclingLoopController::class, 'identifyFiber'])->middleware('throttle:20,1')->name('recycling.identify-fiber');
        Route::get('/recyclage/collectes', [RecyclingScheduleController::class, 'index'])->name('recycling-schedules.index');
        Route::post('/recyclage/collectes', [RecyclingScheduleController::class, 'store'])->name('recycling-schedules.store');
        Route::patch('/recyclage/collectes/{recyclingSchedule}', [RecyclingScheduleController::class, 'toggle'])->name('recycling-schedules.toggle');
        Route::post('/recyclage/collectes/{recyclingSchedule}/lancer', [RecyclingScheduleController::class, 'run'])->name('recycling-schedules.run');
        Route::delete('/recyclage/collectes/{recyclingSchedule}', [RecyclingScheduleController::class, 'destroy'])->name('recycling-schedules.destroy');
        Route::post('/recyclage', [RecyclingLoopController::class, 'store'])->name('recycling.store');
        Route::get('/recyclage/{recyclingLoop}', [RecyclingLoopController::class, 'show'])->name('recycling.show');
        Route::post('/recyclage/{recyclingLoop}/accepter', [RecyclingLoopController::class, 'accept'])->name('recycling.accept');
        Route::post('/recyclage/{recyclingLoop}/refuser', [RecyclingLoopController::class, 'decline'])->name('recycling.decline');
        Route::post('/recyclage/{recyclingLoop}/envoi', [RecyclingLoopController::class, 'ship'])->name('recycling.ship');
        Route::post('/recyclage/{recyclingLoop}/reception', [RecyclingLoopController::class, 'close'])->name('recycling.close');
        Route::get('/recyclage/{recyclingLoop}/document/{type}/{item?}', [RecyclingLoopController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('recycling.document');
        Route::get('/recyclage/{recyclingLoop}/facture', [RecyclingLoopController::class, 'invoice'])->name('recycling.invoice');

        Route::post('/impact/{report}/simulate', ImpactSimulatorController::class)->name('impact.simulate');
        Route::get('/suppliers-performance', [SupplierScorecardController::class, 'brand'])->name('supplier-performance');
        Route::get('/suppliers-risques', [SupplierRiskController::class, 'brand'])->name('suppliers-risk');
        Route::post('/suppliers-risques/alertes', [SupplierRiskController::class, 'sendAlerts'])->middleware('throttle:10,1')->name('suppliers-risk.alerts');

        // Impact ESG : objectifs carbone et anti-greenwashing
        Route::get('/impact-objectifs', [CarbonTargetController::class, 'index'])->name('carbon-targets.index');
        Route::post('/impact-objectifs', [CarbonTargetController::class, 'store'])->name('carbon-targets.store');
        Route::get('/impact-objectifs/{carbonTarget}', [CarbonTargetController::class, 'show'])->name('carbon-targets.show');
        Route::patch('/impact-objectifs/{carbonTarget}', [CarbonTargetController::class, 'update'])->name('carbon-targets.update');
        Route::delete('/impact-objectifs/{carbonTarget}', [CarbonTargetController::class, 'destroy'])->name('carbon-targets.destroy');
        Route::get('/impact-greenwashing', [GreenClaimsController::class, 'index'])->name('green-claims.index');
        Route::post('/impact-greenwashing', [GreenClaimsController::class, 'analyse'])->middleware('throttle:20,1')->name('green-claims.analyse');

        // Copilote IA (LLM Hugging Face avec appels d'outils)
        Route::get('/copilote-ia', [CopilotController::class, 'index'])->name('copilot');
        Route::post('/copilote-ia', [CopilotController::class, 'ask'])->middleware('throttle:30,1')->name('copilot.ask');
    });

    Route::middleware(['role:supplier', EnsureSupplierPasswordChanged::class])->prefix('espace-fournisseur')->name('supplier.')->group(function () {
        Route::get('/recyclage', [RecyclingLoopController::class, 'supplierIndex'])->name('recycling.index');
        Route::get('/recyclage/{recyclingLoop}', [RecyclingLoopController::class, 'show'])->name('recycling.show');
        Route::post('/recyclage/{recyclingLoop}/proposition', [RecyclingLoopController::class, 'propose'])->name('recycling.propose');
        Route::post('/recyclage/{recyclingLoop}/refuser', [RecyclingLoopController::class, 'decline'])->name('recycling.decline');
        Route::post('/recyclage/{recyclingLoop}/reception', [RecyclingLoopController::class, 'receive'])->name('recycling.receive');
        Route::post('/recyclage/{recyclingLoop}/production', [RecyclingLoopController::class, 'transform'])->name('recycling.transform');
        Route::post('/recyclage/{recyclingLoop}/livraison', [RecyclingLoopController::class, 'deliver'])->name('recycling.deliver');
        Route::get('/recyclage/{recyclingLoop}/document/{type}/{item?}', [RecyclingLoopController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('recycling.document');
        Route::get('/recyclage/{recyclingLoop}/facture', [RecyclingLoopController::class, 'invoice'])->name('recycling.invoice');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/recyclage', [RecyclingLoopController::class, 'adminIndex'])->name('recycling.index');
        Route::get('/recyclage/{recyclingLoop}', [RecyclingLoopController::class, 'show'])->name('recycling.show');
        Route::get('/recyclage/{recyclingLoop}/document/{type}/{item?}', [RecyclingLoopController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('recycling.document');
        Route::get('/recyclage/{recyclingLoop}/facture', [RecyclingLoopController::class, 'invoice'])->name('recycling.invoice');

        Route::post('/impact-reports/{report}/simulate', ImpactSimulatorController::class)->name('impact-reports.simulate');
        Route::get('/suppliers-performance', [SupplierScorecardController::class, 'admin'])->name('supplier-performance');
        Route::get('/suppliers-risques', [SupplierRiskController::class, 'admin'])->name('suppliers-risk');
    });
});
