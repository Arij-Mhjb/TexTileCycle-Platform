<?php

/*
| Fonctionnalités ajoutées aux modules d'Ons : seconde vie de la matière (retours marque → fournisseur),
| simulateur ESG et performance fournisseurs (OTIF).
| Chargées par App\Providers\CircularModulesServiceProvider (middleware "web").
*/

use App\Http\Controllers\Modules\ImpactSimulatorController;
use App\Http\Controllers\Modules\SupplierReturnController;
use App\Http\Controllers\Modules\SupplierScorecardController;
use App\Http\Middleware\EnsureSupplierPasswordChanged;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function () {
        // Seconde vie : retours de matière au fournisseur
        Route::get('/supplier-returns', [SupplierReturnController::class, 'brandIndex'])->name('supplier-returns.index');
        Route::get('/supplier-returns/create', [SupplierReturnController::class, 'create'])->name('supplier-returns.create');
        Route::post('/supplier-returns', [SupplierReturnController::class, 'store'])->name('supplier-returns.store');
        Route::get('/supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'show'])->name('supplier-returns.show');
        Route::post('/supplier-returns/{supplierReturn}/ship', [SupplierReturnController::class, 'ship'])->name('supplier-returns.ship');
        Route::get('/supplier-returns/{supplierReturn}/{type}', [SupplierReturnController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('supplier-returns.document');

        Route::post('/impact/{report}/simulate', ImpactSimulatorController::class)->name('impact.simulate');
        Route::get('/suppliers-performance', [SupplierScorecardController::class, 'brand'])->name('supplier-performance');
    });

    Route::middleware(['role:supplier', EnsureSupplierPasswordChanged::class])->prefix('espace-fournisseur')->name('supplier.')->group(function () {
        Route::get('/retours', [SupplierReturnController::class, 'supplierIndex'])->name('returns.index');
        Route::get('/retours/{supplierReturn}', [SupplierReturnController::class, 'show'])->name('returns.show');
        Route::post('/retours/{supplierReturn}/accepter', [SupplierReturnController::class, 'accept'])->name('returns.accept');
        Route::post('/retours/{supplierReturn}/reception', [SupplierReturnController::class, 'receive'])->name('returns.receive');
        Route::post('/retours/{supplierReturn}/seconde-vie', [SupplierReturnController::class, 'complete'])->name('returns.complete');
        Route::get('/retours/{supplierReturn}/{type}', [SupplierReturnController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('returns.document');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/supplier-returns', [SupplierReturnController::class, 'adminIndex'])->name('supplier-returns.index');
        Route::get('/supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'show'])->name('supplier-returns.show');
        Route::patch('/supplier-returns/{supplierReturn}/verify', [SupplierReturnController::class, 'verify'])->name('supplier-returns.verify');
        Route::get('/supplier-returns/{supplierReturn}/{type}', [SupplierReturnController::class, 'document'])->whereIn('type', ['photo', 'proof'])->name('supplier-returns.document');

        Route::post('/impact-reports/{report}/simulate', ImpactSimulatorController::class)->name('impact-reports.simulate');
        Route::get('/suppliers-performance', [SupplierScorecardController::class, 'admin'])->name('supplier-performance');
    });
});
