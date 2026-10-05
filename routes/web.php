<?php

use App\Http\Controllers\AdminEvidenceReviewController;
use App\Http\Controllers\AdminLabelController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\BrandDashboardController;
use App\Http\Controllers\BrandLabelController;
use App\Http\Controllers\BrandProductController;
use App\Http\Controllers\CollectionPointController;
use App\Http\Controllers\ConsumerExploreController;
use App\Http\Controllers\ConsumerHomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\EvidenceController;
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
Route::get('/brands',[PublicBrandController::class,'index'])->name('brands.index');
Route::get('/brands/{brand:slug}',[PublicBrandController::class,'show'])->name('brands.show');

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/dashboard',DashboardRedirectController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function(){
        Route::get('/admin',DashboardController::class)->name('admin.dashboard');
        Route::resources([
            'products'=>ProductController::class,'materials'=>MaterialController::class,'detections'=>DetectionController::class,
            'recovery-programs'=>RecoveryProgramController::class,'collection-points'=>CollectionPointController::class,
            'treatment-results'=>TreatmentResultController::class,'assessments'=>AssessmentController::class,'evidences'=>EvidenceController::class,
        ],['except'=>['show']]);
        Route::patch('/admin/evidences/{evidence}/review',[AdminEvidenceReviewController::class,'update'])->name('admin.evidences.review');
        Route::resource('admin/labels',AdminLabelController::class)->names('admin.labels')->except('show');
    });

    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function(){
        Route::get('/',BrandDashboardController::class)->name('dashboard');
        Route::resource('products',BrandProductController::class)->except('show');
        Route::get('/labels',[BrandLabelController::class,'index'])->name('labels.index');
        Route::get('/labels/{label:slug}',[BrandLabelController::class,'show'])->name('labels.show');
        Route::post('/labels/{label:slug}/apply',[BrandLabelController::class,'apply'])->name('labels.apply');
        Route::post('/assessments/{assessment}/evidences',[BrandLabelController::class,'evidence'])->name('evidences.store');
        Route::post('/assessments/{assessment}/submit',[BrandLabelController::class,'submit'])->name('assessments.submit');
    });

    Route::middleware('role:consumer')->prefix('discover')->name('consumer.')->group(function(){
        Route::get('/',ConsumerHomeController::class)->name('home');
        Route::get('/products',[ConsumerExploreController::class,'products'])->name('products');
        Route::get('/products/{product}',[ConsumerExploreController::class,'product'])->name('products.show');
        Route::get('/programs',[ConsumerExploreController::class,'programs'])->name('programs');
        Route::get('/points',[ConsumerExploreController::class,'points'])->name('points');
    });

    Route::middleware('role:consumer,admin')->group(function(){
        Route::resource('scans',ScanController::class)->only(['index','create','store','show','destroy']);
    });
    Route::middleware('role:consumer,admin')->group(function(){
        Route::resource('product-returns',ProductReturnController::class)->except('show');
    });
});

Route::middleware('auth')->group(function(){
    Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
