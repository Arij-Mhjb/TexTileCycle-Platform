<?php

/*
| Routes des modules : Fournisseurs & traçabilité, Impact ESG, Réparation / Marketplace.
| Chargées par App\Providers\CircularModulesServiceProvider (middleware "web").
*/

use App\Http\Controllers\Modules\AdminImpactCategoryController;
use App\Http\Controllers\Modules\AdminImpactMetricController;
use App\Http\Controllers\Modules\AdminImpactReportController;
use App\Http\Controllers\Modules\AdminMarketplaceController;
use App\Http\Controllers\Modules\AdminRepairCenterController;
use App\Http\Controllers\Modules\AdminRepairOrderController;
use App\Http\Controllers\Modules\AdminSupplierController;
use App\Http\Controllers\Modules\AdminTraceabilityController;
use App\Http\Controllers\Modules\AdminTradeInController;
use App\Http\Controllers\Modules\BrandImpactController;
use App\Http\Controllers\Modules\BrandMarketplaceController;
use App\Http\Controllers\Modules\BrandPurchaseOrderController;
use App\Http\Controllers\Modules\BrandSupplierBatchController;
use App\Http\Controllers\Modules\BrandSupplierController;
use App\Http\Controllers\Modules\BrandTraceabilityController;
use App\Http\Controllers\Modules\ConsumerMarketplaceController;
use App\Http\Controllers\Modules\ConsumerRepairController;
use App\Http\Controllers\Modules\ConsumerTradeInController;
use App\Http\Controllers\Modules\ModuleAiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // 1. Fournisseurs & traçabilité
        Route::get('/suppliers', [AdminSupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/{supplier}', [AdminSupplierController::class, 'show'])->name('suppliers.show');
        Route::patch('/supplier-batches/{batch}/review', [AdminSupplierController::class, 'reviewBatch'])->name('supplier-batches.review');
        Route::patch('/certificates/{certificate}/review', [AdminSupplierController::class, 'reviewCertificate'])->name('certificates.review');
        Route::get('/certificates/{certificate}/document', [AdminSupplierController::class, 'document'])->name('certificates.document');
        Route::post('/suppliers/{supplier}/ai-risk', [ModuleAiController::class, 'adminSupplierRisk'])->name('suppliers.ai-risk');
        Route::get('/traceability', [AdminTraceabilityController::class, 'index'])->name('traceability.index');
        Route::post('/traceability/{link}/check', [AdminTraceabilityController::class, 'check'])->name('traceability.check');

        // 2. Impact ESG
        Route::resource('impact-categories', AdminImpactCategoryController::class)->except('show');
        Route::get('/impact-metrics', [AdminImpactMetricController::class, 'index'])->name('impact-metrics.index');
        Route::post('/impact-metrics', [AdminImpactMetricController::class, 'store'])->name('impact-metrics.store');
        Route::delete('/impact-metrics/{metric}', [AdminImpactMetricController::class, 'destroy'])->name('impact-metrics.destroy');
        Route::get('/impact-reports', [AdminImpactReportController::class, 'index'])->name('impact-reports.index');
        Route::get('/impact-compare', [AdminImpactReportController::class, 'compare'])->name('impact-reports.compare');
        Route::post('/impact-reports', [AdminImpactReportController::class, 'store'])->name('impact-reports.store');
        Route::get('/impact-reports/{report}', [AdminImpactReportController::class, 'show'])->name('impact-reports.show');
        Route::patch('/impact-reports/{report}/validate', [AdminImpactReportController::class, 'validateReport'])->name('impact-reports.validate');
        Route::post('/impact-reports/{report}/ai-recommendations', [ModuleAiController::class, 'adminRecommendations'])->name('impact-reports.ai-recommendations');

        // 3. Réparation / revente / marketplace
        Route::resource('repair-centers', AdminRepairCenterController::class)->except('show');
        Route::get('/repair-orders', [AdminRepairOrderController::class, 'index'])->name('repair-orders.index');
        Route::post('/repair-orders/from-return/{productReturn}', [AdminRepairOrderController::class, 'store'])->name('repair-orders.store');
        Route::get('/repair-orders/{repairOrder}', [AdminRepairOrderController::class, 'show'])->name('repair-orders.show');
        Route::patch('/repair-orders/{repairOrder}/diagnose', [AdminRepairOrderController::class, 'diagnose'])->name('repair-orders.diagnose');
        Route::post('/repair-orders/{repairOrder}/start', [AdminRepairOrderController::class, 'start'])->name('repair-orders.start');
        Route::post('/repair-orders/{repairOrder}/refurbish', [AdminRepairOrderController::class, 'refurbish'])->name('repair-orders.refurbish');
        Route::post('/repair-orders/{repairOrder}/publish', [AdminRepairOrderController::class, 'publish'])->name('repair-orders.publish');
        Route::post('/repair-orders/{repairOrder}/close', [AdminRepairOrderController::class, 'close'])->name('repair-orders.close');
        Route::post('/repair-orders/{repairOrder}/ai-diagnosis', [ModuleAiController::class, 'adminRepairDiagnosis'])->name('repair-orders.ai-diagnosis');
        Route::get('/marketplace', [AdminMarketplaceController::class, 'index'])->name('marketplace.index');
        Route::get('/marketplace/{listing}', [AdminMarketplaceController::class, 'show'])->name('marketplace.show');
        Route::patch('/marketplace-offers/{offer}', [AdminMarketplaceController::class, 'respond'])->name('marketplace.offers.respond');
        Route::post('/marketplace/{listing}/sold', [AdminMarketplaceController::class, 'sold'])->name('marketplace.sold');
        Route::post('/marketplace/{listing}/archive', [AdminMarketplaceController::class, 'archive'])->name('marketplace.archive');
        Route::get('/trade-ins', [AdminTradeInController::class, 'index'])->name('trade-ins.index');
        Route::patch('/trade-ins/{tradeIn}/evaluate', [AdminTradeInController::class, 'evaluate'])->name('trade-ins.evaluate');
        Route::post('/trade-ins/{tradeIn}/complete', [AdminTradeInController::class, 'complete'])->name('trade-ins.complete');
    });

    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function () {
        // 1. Fournisseurs & traçabilité
        Route::resource('suppliers', BrandSupplierController::class);
        Route::post('/suppliers/{supplier}/purchase-orders', [BrandPurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::patch('/purchase-orders/{purchaseOrder}', [BrandPurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::post('/suppliers/{supplier}/batches', [BrandSupplierBatchController::class, 'store'])->name('supplier-batches.store');
        Route::delete('/supplier-batches/{batch}', [BrandSupplierBatchController::class, 'destroy'])->name('supplier-batches.destroy');
        Route::post('/supplier-batches/{batch}/certificates', [BrandSupplierBatchController::class, 'certificate'])->name('certificates.store');
        Route::get('/certificates/{certificate}/document', [BrandSupplierBatchController::class, 'document'])->name('certificates.document');
        Route::post('/suppliers/{supplier}/ai-risk', [ModuleAiController::class, 'brandSupplierRisk'])->name('suppliers.ai-risk');
        Route::get('/traceability', [BrandTraceabilityController::class, 'index'])->name('traceability.index');
        Route::post('/traceability', [BrandTraceabilityController::class, 'store'])->name('traceability.store');
        Route::post('/traceability/{link}/check', [BrandTraceabilityController::class, 'check'])->name('traceability.check');
        Route::delete('/traceability/{link}', [BrandTraceabilityController::class, 'destroy'])->name('traceability.destroy');

        // 2. Impact ESG
        Route::get('/impact', [BrandImpactController::class, 'index'])->name('impact.index');
        Route::post('/impact', [BrandImpactController::class, 'store'])->name('impact.store');
        Route::get('/impact/{report}', [BrandImpactController::class, 'show'])->name('impact.show');
        Route::post('/impact/{report}/ai-recommendations', [ModuleAiController::class, 'brandRecommendations'])->name('impact.ai-recommendations');

        // 3. Marketplace circulaire
        Route::get('/marketplace', [BrandMarketplaceController::class, 'index'])->name('marketplace.index');
        Route::post('/marketplace', [BrandMarketplaceController::class, 'store'])->name('marketplace.store');
        Route::patch('/marketplace-offers/{offer}', [BrandMarketplaceController::class, 'respond'])->name('marketplace.offers.respond');
        Route::post('/marketplace/{listing}/sold', [BrandMarketplaceController::class, 'sold'])->name('marketplace.sold');
    });

    Route::middleware('role:consumer')->prefix('discover')->name('consumer.')->group(function () {
        Route::get('/marketplace', [ConsumerMarketplaceController::class, 'index'])->name('marketplace.index');
        Route::get('/marketplace/{listing}', [ConsumerMarketplaceController::class, 'show'])->name('marketplace.show');
        Route::post('/marketplace/{listing}/offers', [ConsumerMarketplaceController::class, 'offer'])->name('marketplace.offer');
        Route::get('/repairs', [ConsumerRepairController::class, 'index'])->name('repairs.index');
        Route::post('/repairs', [ConsumerRepairController::class, 'store'])->name('repairs.store');
        Route::get('/trade-ins', [ConsumerTradeInController::class, 'index'])->name('trade-ins.index');
        Route::post('/trade-ins', [ConsumerTradeInController::class, 'store'])->name('trade-ins.store');
        Route::post('/trade-ins/{tradeIn}/accept', [ConsumerTradeInController::class, 'accept'])->name('trade-ins.accept');
        Route::post('/trade-ins/{tradeIn}/decline', [ConsumerTradeInController::class, 'decline'])->name('trade-ins.decline');
    });
});
