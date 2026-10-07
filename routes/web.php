<?php

use App\Http\Controllers\AdminEvidenceReviewController;
use App\Http\Controllers\AdminCollectorController;
use App\Http\Controllers\AdminLabelController;
use App\Http\Controllers\AdminMaterialReviewController;
use App\Http\Controllers\AdminProductReviewController;
use App\Http\Controllers\AdminPassportProposalController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\BrandDashboardController;
use App\Http\Controllers\BrandEvidenceCommentController;
use App\Http\Controllers\BrandImportController;
use App\Http\Controllers\BrandMaterialMatchController;
use App\Http\Controllers\BrandMaterialRequestController;
use App\Http\Controllers\BrandLabelController;
use App\Http\Controllers\BrandProductController;
use App\Http\Controllers\BrandPassportProposalController;
use App\Http\Controllers\BrandProfileController;
use App\Http\Controllers\BrandNotificationController;
use App\Http\Controllers\CollectionPointController;
use App\Http\Controllers\CollectorReturnController;
use App\Http\Controllers\ConsumerExploreController;
use App\Http\Controllers\ConsumerHomeController;
use App\Http\Controllers\ConsumerNotificationController;
use App\Http\Controllers\ConsumerMaterialTransferController;
use App\Http\Controllers\ConsumerWalletController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceDownloadController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReturnController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBrandController;
use App\Http\Controllers\RecoveryProgramController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TreatmentResultController;
use Illuminate\Support\Facades\Route;

Route::view('/','welcome')->name('home');
Route::view('/brand-application-pending', 'auth.brand-pending')->name('brand.application-pending');
Route::get('/brands',[PublicBrandController::class,'index'])->name('brands.index');
Route::get('/brands/{brand:slug}',[PublicBrandController::class,'show'])->name('brands.show');

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/dashboard',DashboardRedirectController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function(){
        Route::get('/admin/roadmaps', [\App\Http\Controllers\AdminRoadmapController::class, 'index'])->name('admin.roadmaps.index');
        Route::get('/admin/roadmaps/{roadmap}', [\App\Http\Controllers\AdminRoadmapController::class, 'show'])->name('admin.roadmaps.show');
        Route::patch('/admin/roadmap-submissions/{submission}', [\App\Http\Controllers\AdminRoadmapController::class, 'review'])->name('admin.roadmaps.review');
        Route::get('/admin/brand-applications', [\App\Http\Controllers\AdminBrandApplicationController::class, 'index'])->name('admin.brand-applications.index');
        Route::get('/admin/brand-applications/{application}', [\App\Http\Controllers\AdminBrandApplicationController::class, 'show'])->name('admin.brand-applications.show');
        Route::post('/admin/brand-applications/{application}/analyse', [\App\Http\Controllers\AdminBrandApplicationController::class, 'analyse'])->name('admin.brand-applications.analyse');
        Route::post('/admin/brand-applications/{application}/approve', [\App\Http\Controllers\AdminBrandApplicationController::class, 'approve'])->name('admin.brand-applications.approve');
        Route::get('/admin/collectors', [AdminCollectorController::class, 'index'])->name('admin.collectors.index');
        Route::post('/admin/collectors', [AdminCollectorController::class, 'store'])->name('admin.collectors.store');
        Route::delete('/admin/collectors/{collector}', [AdminCollectorController::class, 'destroy'])->name('admin.collectors.destroy');
        Route::get('/admin',DashboardController::class)->name('admin.dashboard');
        Route::resources([
            'products'=>ProductController::class,'materials'=>MaterialController::class,'detections'=>DetectionController::class,
            'recovery-programs'=>RecoveryProgramController::class,'collection-points'=>CollectionPointController::class,
            'treatment-results'=>TreatmentResultController::class,'assessments'=>AssessmentController::class,'evidences'=>EvidenceController::class,
        ],['except'=>['show']]);
        Route::patch('/admin/evidences/{evidence}/review',[AdminEvidenceReviewController::class,'update'])->name('admin.evidences.review');
        Route::resource('admin/labels',AdminLabelController::class)->names('admin.labels')->except('show');
        Route::patch('/admin/products/{product}/approve',[AdminProductReviewController::class,'approve'])->name('admin.products.approve');
        Route::patch('/admin/products/{product}/reject',[AdminProductReviewController::class,'reject'])->name('admin.products.reject');
        Route::get('/admin/material-reviews',[AdminMaterialReviewController::class,'index'])->name('admin.material-reviews.index');
        Route::patch('/admin/material-reviews/{productReturn}',[AdminMaterialReviewController::class,'update'])->name('admin.material-reviews.update');
        Route::get('/admin/passport-proposals',[AdminPassportProposalController::class,'index'])->name('admin.passport-proposals.index');
        Route::get('/admin/passport-proposals/{passportProposal}',[AdminPassportProposalController::class,'show'])->name('admin.passport-proposals.show');
        Route::patch('/admin/passport-proposals/{passportProposal}/partner-points',[AdminPassportProposalController::class,'updateCollectionPoints'])->name('admin.passport-proposals.partner-points');
        Route::patch('/admin/passport-proposals/{passportProposal}',[AdminPassportProposalController::class,'review'])->name('admin.passport-proposals.review');
    });

    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function(){
        Route::resource('roadmaps', \App\Http\Controllers\BrandRoadmapController::class)->parameters(['roadmaps' => 'roadmap']);
        Route::post('/roadmaps/{roadmap}/restore', [\App\Http\Controllers\BrandRoadmapController::class, 'restore'])->name('roadmaps.restore');
        Route::patch('/roadmap-steps/{step}', [\App\Http\Controllers\BrandRoadmapController::class, 'step'])->name('roadmaps.steps.update');
        Route::post('/roadmap-steps/{step}/submissions', [\App\Http\Controllers\BrandRoadmapController::class, 'submit'])->name('roadmaps.steps.submit');
        Route::get('/',BrandDashboardController::class)->name('dashboard');
        Route::resource('products',BrandProductController::class)->except('show');
        Route::get('/products-trash',[BrandProductController::class,'trash'])->name('products.trash');
        Route::post('/products-trash/{id}/restore',[BrandProductController::class,'restore'])->name('products.restore');
        Route::post('/products/{product}/submit',[BrandProductController::class,'submit'])->name('products.submit');
        Route::post('/products/{product}/duplicate',[BrandProductController::class,'duplicate'])->name('products.duplicate');
        Route::post('/products/{product}/archive',[BrandProductController::class,'archive'])->name('products.archive');
        Route::get('/passport-proposals',[BrandPassportProposalController::class,'index'])->name('passport-proposals.index');
        Route::get('/passport-proposals/{passportProposal}',[BrandPassportProposalController::class,'show'])->name('passport-proposals.show');
        Route::patch('/passport-proposals/{passportProposal}',[BrandPassportProposalController::class,'review'])->name('passport-proposals.review');
        Route::get('/products/import',[BrandImportController::class,'create'])->name('products.import');
        Route::post('/products/import',[BrandImportController::class,'store'])->name('products.import.store');
        Route::get('/profile',[BrandProfileController::class,'edit'])->name('profile.edit');
        Route::patch('/profile',[BrandProfileController::class,'update'])->name('profile.update');
        Route::post('/notifications/read-all',[BrandNotificationController::class,'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read',[BrandNotificationController::class,'read'])->name('notifications.read');
        Route::get('/labels',[BrandLabelController::class,'index'])->name('labels.index');
        Route::get('/labels/{label:slug}',[BrandLabelController::class,'show'])->name('labels.show');
        Route::post('/labels/{label:slug}/apply',[BrandLabelController::class,'apply'])->name('labels.apply');
        Route::post('/assessments/{assessment}/evidences',[BrandLabelController::class,'evidence'])->name('evidences.store');
        Route::post('/assessments/{assessment}/submit',[BrandLabelController::class,'submit'])->name('assessments.submit');
        Route::post('/evidences/{evidence}/comments',[BrandEvidenceCommentController::class,'store'])->name('evidences.comments.store');
        Route::get('/material-requests',[BrandMaterialRequestController::class,'index'])->name('material-requests.index');
        Route::post('/material-requests',[BrandMaterialRequestController::class,'store'])->name('material-requests.store');
        Route::delete('/material-requests/{materialRequest}',[BrandMaterialRequestController::class,'destroy'])->name('material-requests.destroy');
        Route::get('/material-matches',[BrandMaterialMatchController::class,'index'])->name('material-matches.index');
        Route::post('/material-matches/{materialMatch}/accept',[BrandMaterialMatchController::class,'accept'])->name('material-matches.accept');
        Route::post('/material-matches/{materialMatch}/receive',[BrandMaterialMatchController::class,'receive'])->name('material-matches.receive');
        Route::post('/material-matches/{materialMatch}/verify',[BrandMaterialMatchController::class,'verify'])->name('material-matches.verify');
        Route::post('/material-matches/{materialMatch}/reject',[BrandMaterialMatchController::class,'reject'])->name('material-matches.reject');
    });

    Route::middleware('role:consumer')->prefix('discover')->name('consumer.')->group(function(){
        Route::get('/',ConsumerHomeController::class)->name('home');
        Route::get('/products',[ConsumerExploreController::class,'products'])->name('products');
        Route::get('/products/{product}',[ConsumerExploreController::class,'product'])->name('products.show');
        Route::get('/programs',[ConsumerExploreController::class,'programs'])->name('programs');
        Route::get('/points',[ConsumerExploreController::class,'points'])->name('points');
        Route::get('/wallet',ConsumerWalletController::class)->name('wallet');
        Route::post('/notifications/read-all',[ConsumerNotificationController::class,'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read',[ConsumerNotificationController::class,'read'])->name('notifications.read');
    });

    Route::middleware('role:consumer,admin')->group(function(){
        Route::resource('scans',ScanController::class)->only(['index','create','store','show','destroy']);
        Route::post('/scans/{scan}/confirm/{product}',[ScanController::class,'confirm'])->name('scans.confirm');
        Route::post('/scans/{scan}/confirm-data',[ScanController::class,'confirmData'])->name('scans.confirm-data');
        Route::patch('/scans/{scan}/supplement-data',[ScanController::class,'supplementData'])->name('scans.supplement-data');
        Route::post('/scans/{scan}/reanalyse',[ScanController::class,'reanalyse'])->name('scans.reanalyse');
    });
    Route::middleware('role:consumer,admin')->group(function(){
        Route::resource('product-returns',ProductReturnController::class);
        Route::post('/product-returns/{productReturn}/declare-deposit',[ProductReturnController::class,'declareDeposit'])->middleware('role:consumer')->name('product-returns.declare-deposit');
        Route::post('/material-transfers/{materialMatch}',[ConsumerMaterialTransferController::class,'store'])->middleware('role:consumer')->name('consumer.material-transfers.store');
    });
    Route::middleware('role:collector,admin')->prefix('collector')->name('collector.')->group(function(){
        Route::get('/returns',[CollectorReturnController::class,'index'])->name('returns.index');
        Route::patch('/returns/{productReturn}',[CollectorReturnController::class,'update'])->name('returns.update');
    });
    Route::middleware('role:admin,brand')->get('/evidences/{evidence}/download',EvidenceDownloadController::class)->name('evidences.download');
    Route::middleware('role:admin,brand')->get('/roadmap-proofs/{submission}/{index}', \App\Http\Controllers\RoadmapProofController::class)->whereNumber('index')->name('roadmaps.proofs.download');
});

Route::middleware('auth')->group(function(){
    Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

