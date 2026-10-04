<?php

use App\Http\Controllers\AssessmentController;
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
use App\Http\Controllers\RecoveryProgramController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TreatmentResultController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', DashboardController::class)->name('admin.dashboard');
        Route::resources([
            'products' => ProductController::class,
            'materials' => MaterialController::class,
            'detections' => DetectionController::class,
            'recovery-programs' => RecoveryProgramController::class,
            'collection-points' => CollectionPointController::class,
            'treatment-results' => TreatmentResultController::class,
            'assessments' => AssessmentController::class,
            'evidences' => EvidenceController::class,
        ], ['except' => ['show']]);
    });

    Route::middleware('role:consumer')->prefix('discover')->name('consumer.')->group(function () {
        Route::get('/', ConsumerHomeController::class)->name('home');
        Route::get('/products', [ConsumerExploreController::class, 'products'])->name('products');
        Route::get('/programs', [ConsumerExploreController::class, 'programs'])->name('programs');
        Route::get('/points', [ConsumerExploreController::class, 'points'])->name('points');
    });

    Route::resource('scans', ScanController::class)->except('show');
    Route::resource('product-returns', ProductReturnController::class)->except('show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
