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
use App\Http\Controllers\Modules\NotificationCenterController;
use App\Http\Controllers\Modules\SupplierAreaController;
use App\Http\Controllers\Modules\SupplierInvitationController;
use App\Http\Controllers\Modules\SupplierProfileController;
use App\Http\Middleware\EnsureSupplierPasswordChanged;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Cloche de notifications commune (admin, marque, fournisseur, consommateur)
    Route::post('/centre-notifications/tout-lire', [NotificationCenterController::class, 'readAll'])->name('notification-center.read-all');
    Route::post('/centre-notifications/{notification}', [NotificationCenterController::class, 'open'])->whereNumber('notification')->name('notification-center.open');

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

    // Espace du fournisseur connecté (rôle « supplier »). Mot de passe provisoire à changer d'abord.
    Route::middleware('role:supplier')->prefix('espace-fournisseur')->name('supplier.')->group(function () {
        Route::get('/mon-compte', [SupplierProfileController::class, 'edit'])->name('account.edit');
        Route::put('/mon-compte', [SupplierProfileController::class, 'update'])->name('account.update');
        Route::put('/mon-compte/identifiant', [SupplierProfileController::class, 'updateEmail'])->name('account.email');
        Route::put('/mon-compte/mot-de-passe', [SupplierProfileController::class, 'updatePassword'])->name('password.update');
        Route::redirect('/mot-de-passe', '/espace-fournisseur/mon-compte#mot-de-passe')->name('password.edit');
        Route::middleware(EnsureSupplierPasswordChanged::class)->group(function () {
            Route::get('/', [SupplierAreaController::class, 'index'])->name('dashboard');
            Route::get('/commandes/{purchaseOrder}', [SupplierAreaController::class, 'order'])->name('orders.show');
            Route::post('/commandes/{purchaseOrder}/reponse', [SupplierAreaController::class, 'answerOrder'])->name('orders.answer');
            Route::post('/commandes/{purchaseOrder}/expedition', [SupplierAreaController::class, 'shipOrder'])->name('orders.ship');
            Route::get('/lots', [SupplierAreaController::class, 'batches'])->name('batches.index');
            Route::post('/lots/{batch}/certificats', [SupplierAreaController::class, 'addCertificate'])->name('batches.certificate');
            Route::get('/certificats/{certificate}/document', [SupplierAreaController::class, 'certificateDocument'])->name('certificates.document');
        });
    });

    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function () {
        // 1. Fournisseurs & traçabilité
        Route::resource('suppliers', BrandSupplierController::class);
        Route::post('/suppliers/{supplier}/purchase-orders', [BrandPurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::patch('/purchase-orders/{purchaseOrder}', [BrandPurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::get('/purchase-orders/{purchaseOrder}', [BrandPurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/counter', [BrandPurchaseOrderController::class, 'decideCounter'])->name('purchase-orders.counter');
        Route::post('/purchase-orders/{purchaseOrder}/close', [BrandPurchaseOrderController::class, 'closeShort'])->name('purchase-orders.close');
        Route::post('/supplier-batches/{batch}/receive', [BrandPurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::patch('/suppliers/{supplier}/suspend', [BrandSupplierController::class, 'suspend'])->name('suppliers.suspend');
        Route::post('/suppliers/{supplier}/access', [BrandSupplierController::class, 'access'])->name('suppliers.access');
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
        Route::post('/marketplace/{listing}/buy-with-points', [ConsumerMarketplaceController::class, 'buyWithPoints'])->name('marketplace.buy-with-points');
        Route::post('/marketplace-orders/{marketplaceOrder}/checkout', [ConsumerMarketplaceController::class, 'checkout'])->name('marketplace.checkout');
        Route::post('/marketplace-orders/{marketplaceOrder}/received', [ConsumerMarketplaceController::class, 'confirmReceipt'])->name('marketplace.received');
        Route::get('/repairs', [ConsumerRepairController::class, 'index'])->name('repairs.index');
        Route::post('/repairs', [ConsumerRepairController::class, 'store'])->name('repairs.store');
        Route::post('/repairs/{repairOrder}/decision', [ConsumerRepairController::class, 'respond'])->name('repairs.respond');
        Route::get('/trade-ins', [ConsumerTradeInController::class, 'index'])->name('trade-ins.index');
        Route::post('/trade-ins', [ConsumerTradeInController::class, 'store'])->name('trade-ins.store');
        Route::post('/trade-ins/{tradeIn}/accept', [ConsumerTradeInController::class, 'accept'])->name('trade-ins.accept');
        Route::post('/trade-ins/{tradeIn}/decline', [ConsumerTradeInController::class, 'decline'])->name('trade-ins.decline');
        Route::post('/trade-ins/{tradeIn}/deposit', [ConsumerTradeInController::class, 'deposit'])->name('trade-ins.deposit');
    });
});

// Activation du compte fournisseur par lien d'invitation : page publique, protégée par le jeton secret et limitée en débit.
Route::get('/invitation-fournisseur/{token}', [SupplierInvitationController::class, 'show'])->middleware('throttle:30,1')->name('supplier.invitation.show');
Route::post('/invitation-fournisseur/{token}', [SupplierInvitationController::class, 'accept'])->middleware('throttle:10,1')->name('supplier.invitation.accept');
