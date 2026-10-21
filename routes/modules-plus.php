<?php

/*
| Fonctionnalités ajoutées aux modules d'Ons : boucle de recyclage marque ↔ fournisseur (seconde vie de la matière),
| simulateur ESG et performance fournisseurs (OTIF).
| Chargées par App\Providers\CircularModulesServiceProvider (middleware "web").
*/

use App\Http\Controllers\Modules\ImpactSimulatorController;
use App\Http\Controllers\Modules\RecyclingLoopController;
use App\Http\Controllers\Modules\SupplierScorecardController;
use App\Http\Middleware\EnsureSupplierPasswordChanged;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function () {
        // Seconde vie : boucle de recyclage avec le fournisseur
        Route::get('/recyclage', [RecyclingLoopController::class, 'brandIndex'])->name('recycling.index');
        Route::get('/recyclage/nouveau', [RecyclingLoopController::class, 'create'])->name('recycling.create');
        Route::post('/recyclage', [RecyclingLoopController::class, 'store'])->name('recycling.store');
        Route::get('/recyclage/{recyclingLoop}', [RecyclingLoopController::class, 'show'])->name('recycling.show');
        Route::post('/recyclage/{recyclingLoop}/accepter', [RecyclingLoopController::class, 'accept'])->name('recycling.accept');
        Route::post('/recyclage/{recyclingLoop}/refuser', [RecyclingLoopController::class, 'decline'])->name('recycling.decline');
        Route::post('/recyclage/{recyclingLoop}/envoi', [RecyclingLoopController::class, 'ship'])->name('recycling.ship');
        Route::post('/recyclage/{recyclingLoop}/reception', [RecyclingLoopController::class, 'close'])->name('recycling.close');
        Route::get('/recyclage/{recyclingLoop}/document/{type}/{item?}', [RecyclingLoopController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('recycling.document');

        Route::post('/impact/{report}/simulate', ImpactSimulatorController::class)->name('impact.simulate');
        Route::get('/suppliers-performance', [SupplierScorecardController::class, 'brand'])->name('supplier-performance');
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
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/recyclage', [RecyclingLoopController::class, 'adminIndex'])->name('recycling.index');
        Route::get('/recyclage/{recyclingLoop}', [RecyclingLoopController::class, 'show'])->name('recycling.show');
        Route::get('/recyclage/{recyclingLoop}/document/{type}/{item?}', [RecyclingLoopController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('recycling.document');

        Route::post('/impact-reports/{report}/simulate', ImpactSimulatorController::class)->name('impact-reports.simulate');
        Route::get('/suppliers-performance', [SupplierScorecardController::class, 'admin'])->name('supplier-performance');
    });
});
