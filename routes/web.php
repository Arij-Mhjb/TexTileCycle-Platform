<?php
use App\Http\Controllers\{AssessmentController,CollectionPointController,DashboardController,DetectionController,EvidenceController,MaterialController,ProductController,ProductReturnController,ProfileController,RecoveryProgramController,ScanController,TreatmentResultController};
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resources([
        'products' => ProductController::class,
        'materials' => MaterialController::class,
        'scans' => ScanController::class,
        'detections' => DetectionController::class,
        'recovery-programs' => RecoveryProgramController::class,
        'collection-points' => CollectionPointController::class,
        'product-returns' => ProductReturnController::class,
        'treatment-results' => TreatmentResultController::class,
        'assessments' => AssessmentController::class,
        'evidences' => EvidenceController::class,
    ], ['except' => ['show']]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
