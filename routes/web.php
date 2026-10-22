<?php

use App\Http\Controllers\AdminArticleController;
use App\Http\Controllers\AdminBrandApplicationController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ArticleReactionController;
use App\Http\Controllers\AdminBlogStatsController;
use App\Http\Controllers\AdminChatbotCategoryController;
use App\Http\Controllers\AdminChatbotQuestionController;
use App\Http\Controllers\AdminChatbotStatsController;
use App\Http\Controllers\ChatWidgetController;
use App\Http\Controllers\AdminConsumerClaimController;
use App\Http\Controllers\AdminCollectorController;
use App\Http\Controllers\AdminEvidenceReviewController;
use App\Http\Controllers\AdminLabelController;
use App\Http\Controllers\AdminMaterialReviewController;
use App\Http\Controllers\AdminPassportProposalController;
use App\Http\Controllers\AdminProductReviewController;
use App\Http\Controllers\AdminRoadmapController;
use App\Http\Controllers\AdminRewardItemController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\BrandDashboardController;
use App\Http\Controllers\BrandTeamController;
use App\Http\Controllers\BrandMemberInvitationController;
use App\Http\Controllers\BrandMemberTaskController;
use App\Http\Controllers\BrandAssistantController;
use App\Http\Controllers\BrandEvidenceCommentController;
use App\Http\Controllers\BrandImportController;
use App\Http\Controllers\BrandLabelController;
use App\Http\Controllers\BrandMaterialMatchController;
use App\Http\Controllers\BrandMaterialRequestController;
use App\Http\Controllers\BrandMaterialUsageController;
use App\Http\Controllers\BrandNotificationController;
use App\Http\Controllers\BrandPassportProposalController;
use App\Http\Controllers\BrandProductController;
use App\Http\Controllers\BrandProfileController;
use App\Http\Controllers\BrandRoadmapController;
use App\Http\Controllers\CollectionPointController;
use App\Http\Controllers\CollectorReturnController;
use App\Http\Controllers\ConsumerExploreController;
use App\Http\Controllers\ConsumerClaimController;
use App\Http\Controllers\ConsumerDataExportController;
use App\Http\Controllers\ConsumerHomeController;
use App\Http\Controllers\ConsumerMaterialTransferController;
use App\Http\Controllers\ConsumerNotificationController;
use App\Http\Controllers\ConsumerRewardController;
use App\Http\Controllers\ConsumerWalletController;
use App\Http\Controllers\ConsumerWardrobeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceDownloadController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductLabelRecommendationController;
use App\Http\Controllers\ProductReturnController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBrandController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\RecoveryProgramController;
use App\Http\Controllers\RepairPartnerController;
use App\Http\Controllers\RepairWorkshopController;
use App\Http\Controllers\RoadmapProofController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TreatmentResultController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/brand-application-pending', 'auth.brand-pending')->name('brand.application-pending');
Route::get('/brands', [PublicBrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [PublicBrandController::class, 'show'])->name('brands.show');
Route::get('/invitation-equipe/{token}', [BrandMemberInvitationController::class,'show'])->middleware('throttle:30,1')->name('brand-member.invitation.show');
Route::post('/invitation-equipe/{token}', [BrandMemberInvitationController::class,'store'])->middleware('throttle:10,1')->name('brand-member.invitation.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/consumer-claims', [AdminConsumerClaimController::class, 'index'])->name('admin.consumer-claims.index');
        Route::patch('/admin/consumer-claims/{consumerClaim}', [AdminConsumerClaimController::class, 'update'])->name('admin.consumer-claims.update');
        Route::get('/admin/reward-items', [AdminRewardItemController::class, 'index'])->name('admin.reward-items.index');
        Route::post('/admin/reward-items', [AdminRewardItemController::class, 'store'])->name('admin.reward-items.store');
        Route::patch('/admin/reward-items/{rewardItem}', [AdminRewardItemController::class, 'update'])->name('admin.reward-items.update');
        Route::delete('/admin/reward-items/{rewardItem}', [AdminRewardItemController::class, 'destroy'])->name('admin.reward-items.destroy');
        Route::post('/admin/reward-redemptions/use', [AdminRewardItemController::class, 'consume'])->name('admin.reward-redemptions.consume');
        Route::get('/admin/roadmaps', [AdminRoadmapController::class, 'index'])->name('admin.roadmaps.index');
        Route::get('/admin/roadmaps/{roadmap}', [AdminRoadmapController::class, 'show'])->name('admin.roadmaps.show');
        Route::patch('/admin/roadmap-submissions/{submission}', [AdminRoadmapController::class, 'review'])->name('admin.roadmaps.review');
        Route::get('/admin/brand-applications', [AdminBrandApplicationController::class, 'index'])->name('admin.brand-applications.index');
        Route::get('/admin/brand-applications/{application}', [AdminBrandApplicationController::class, 'show'])->name('admin.brand-applications.show');
        Route::post('/admin/brand-applications/{application}/analyse', [AdminBrandApplicationController::class, 'analyse'])->name('admin.brand-applications.analyse');
        Route::post('/admin/brand-applications/{application}/approve', [AdminBrandApplicationController::class, 'approve'])->name('admin.brand-applications.approve');
        Route::get('/admin/collectors', [AdminCollectorController::class, 'index'])->name('admin.collectors.index');
        Route::post('/admin/collectors', [AdminCollectorController::class, 'store'])->name('admin.collectors.store');
        Route::delete('/admin/collectors/{collector}', [AdminCollectorController::class, 'destroy'])->name('admin.collectors.destroy');
        Route::get('/admin', DashboardController::class)->name('admin.dashboard');
        Route::resource('admin/categories', AdminCategoryController::class)->names('admin.categories')->except('show');
        Route::resource('admin/articles', AdminArticleController::class)->names('admin.articles')->except('show');
        Route::get('/admin/blog-stats', AdminBlogStatsController::class)->name('admin.blog-stats.index');
        Route::get('/admin/chatbot-stats', AdminChatbotStatsController::class)->name('admin.chatbot-stats.index');
        Route::resource('admin/chatbot-categories', AdminChatbotCategoryController::class)->names('admin.chatbot-categories')->except('show');
        Route::resource('admin/chatbot-questions', AdminChatbotQuestionController::class)->names('admin.chatbot-questions')->except('show');
        Route::resources([
            'products' => ProductController::class, 'materials' => MaterialController::class, 'detections' => DetectionController::class,
            'recovery-programs' => RecoveryProgramController::class, 'collection-points' => CollectionPointController::class,
            'treatment-results' => TreatmentResultController::class, 'assessments' => AssessmentController::class, 'evidences' => EvidenceController::class,
        ], ['except' => ['show']]);
        Route::patch('/admin/evidences/{evidence}/review', [AdminEvidenceReviewController::class, 'update'])->name('admin.evidences.review');
        Route::resource('admin/labels', AdminLabelController::class)->names('admin.labels')->except('show');
        Route::patch('/admin/products/{product}/approve', [AdminProductReviewController::class, 'approve'])->name('admin.products.approve');
        Route::patch('/admin/products/{product}/reject', [AdminProductReviewController::class, 'reject'])->name('admin.products.reject');
        Route::get('/admin/material-reviews', [AdminMaterialReviewController::class, 'index'])->name('admin.material-reviews.index');
        Route::patch('/admin/material-reviews/{productReturn}', [AdminMaterialReviewController::class, 'update'])->name('admin.material-reviews.update');
        Route::get('/admin/passport-proposals', [AdminPassportProposalController::class, 'index'])->name('admin.passport-proposals.index');
        Route::get('/admin/passport-proposals/{passportProposal}', [AdminPassportProposalController::class, 'show'])->name('admin.passport-proposals.show');
        Route::patch('/admin/passport-proposals/{passportProposal}/partner-points', [AdminPassportProposalController::class, 'updateCollectionPoints'])->name('admin.passport-proposals.partner-points');
        Route::patch('/admin/passport-proposals/{passportProposal}', [AdminPassportProposalController::class, 'review'])->name('admin.passport-proposals.review');
    });

    Route::middleware('role:brand')->prefix('brand')->name('brand.')->group(function () {
        Route::post('/assistant', BrandAssistantController::class)->middleware('throttle:20,1')->name('assistant.ask');
        Route::resource('roadmaps', BrandRoadmapController::class)->parameters(['roadmaps' => 'roadmap']);
        Route::get('/product-label-recommendations', [ProductLabelRecommendationController::class, 'index'])->name('product-label-recommendations.index');
        Route::post('/product-label-recommendations/analyse', [ProductLabelRecommendationController::class, 'analyse'])->name('product-label-recommendations.analyse');
        Route::post('/product-label-recommendations/{recommendation}/assign', [ProductLabelRecommendationController::class, 'assign'])->name('product-label-recommendations.assign');
        Route::post('/roadmaps/{roadmap}/restore', [BrandRoadmapController::class, 'restore'])->name('roadmaps.restore');
        Route::patch('/roadmaps/{roadmap}/assign', [BrandRoadmapController::class, 'assign'])->name('roadmaps.assign');
        Route::patch('/roadmap-steps/{step}', [BrandRoadmapController::class, 'step'])->name('roadmaps.steps.update');
        Route::post('/roadmap-steps/{step}/submissions', [BrandRoadmapController::class, 'submit'])->name('roadmaps.steps.submit');
        Route::get('/', BrandDashboardController::class)->name('dashboard');
        Route::resource('products', BrandProductController::class)->except('show');
        Route::get('/products-trash', [BrandProductController::class, 'trash'])->name('products.trash');
        Route::post('/products-trash/{id}/restore', [BrandProductController::class, 'restore'])->name('products.restore');
        Route::post('/products/{product}/submit', [BrandProductController::class, 'submit'])->name('products.submit');
        Route::post('/products/{product}/duplicate', [BrandProductController::class, 'duplicate'])->name('products.duplicate');
        Route::post('/products/{product}/archive', [BrandProductController::class, 'archive'])->name('products.archive');
        Route::get('/passport-proposals', [BrandPassportProposalController::class, 'index'])->name('passport-proposals.index');
        Route::get('/passport-proposals/{passportProposal}', [BrandPassportProposalController::class, 'show'])->name('passport-proposals.show');
        Route::patch('/passport-proposals/{passportProposal}', [BrandPassportProposalController::class, 'review'])->name('passport-proposals.review');
        Route::get('/products/import', [BrandImportController::class, 'create'])->name('products.import');
        Route::post('/products/import', [BrandImportController::class, 'store'])->name('products.import.store');
        Route::get('/profile', [BrandProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [BrandProfileController::class, 'update'])->name('profile.update');
        Route::get('/team', [BrandTeamController::class,'index'])->name('team.index');
        Route::post('/team', [BrandTeamController::class,'store'])->name('team.store');
        Route::post('/team/{member}/resend', [BrandTeamController::class,'resend'])->name('team.resend');
        Route::post('/notifications/read-all', [BrandNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [BrandNotificationController::class, 'read'])->name('notifications.read');
        Route::get('/labels', [BrandLabelController::class, 'index'])->name('labels.index');
        Route::get('/labels/{label:slug}', [BrandLabelController::class, 'show'])->name('labels.show');
        Route::post('/labels/{label:slug}/apply', [BrandLabelController::class, 'apply'])->name('labels.apply');
        Route::post('/assessments/{assessment}/evidences', [BrandLabelController::class, 'evidence'])->name('evidences.store');
        Route::post('/assessments/{assessment}/submit', [BrandLabelController::class, 'submit'])->name('assessments.submit');
        Route::post('/evidences/{evidence}/comments', [BrandEvidenceCommentController::class, 'store'])->name('evidences.comments.store');
        Route::get('/material-requests', [BrandMaterialRequestController::class, 'index'])->name('material-requests.index');
        Route::post('/material-requests', [BrandMaterialRequestController::class, 'store'])->name('material-requests.store');
        Route::delete('/material-requests/{materialRequest}', [BrandMaterialRequestController::class, 'destroy'])->name('material-requests.destroy');
        Route::post('/material-matches/{materialMatch}/usage/assign', [BrandMaterialUsageController::class, 'assign'])->name('material-usage.assign');
        Route::post('/material-matches/{materialMatch}/usage', [BrandMaterialUsageController::class, 'update'])->name('material-usage.update');
        Route::get('/material-matches', [BrandMaterialMatchController::class, 'index'])->name('material-matches.index');
        Route::post('/material-matches/{materialMatch}/accept', [BrandMaterialMatchController::class, 'accept'])->name('material-matches.accept');
        Route::post('/material-matches/{materialMatch}/receive', [BrandMaterialMatchController::class, 'receive'])->name('material-matches.receive');
        Route::post('/material-matches/{materialMatch}/verify', [BrandMaterialMatchController::class, 'verify'])->name('material-matches.verify');
        Route::post('/material-matches/{materialMatch}/reject', [BrandMaterialMatchController::class, 'reject'])->name('material-matches.reject');
    });
    Route::middleware('role:brand_member,brand')->prefix('mes-taches')->name('member.tasks.')->group(function(){
        Route::get('/',[BrandMemberTaskController::class,'index'])->name('index');
        Route::get('/{step}',[BrandMemberTaskController::class,'show'])->name('show');
        Route::post('/{step}/submit',[BrandMemberTaskController::class,'submit'])->name('submit');
    });

    Route::middleware('role:consumer')->prefix('discover')->name('consumer.')->group(function () {
        Route::get('/', ConsumerHomeController::class)->name('home');
        Route::get('/products', [ConsumerExploreController::class, 'products'])->name('products');
        Route::get('/products/{product}', [ConsumerExploreController::class, 'product'])->name('products.show');
        Route::get('/programs', [ConsumerExploreController::class, 'programs'])->name('programs');
        Route::get('/points', [ConsumerExploreController::class, 'points'])->name('points');
        Route::get('/wallet', ConsumerWalletController::class)->name('wallet');
        Route::get('/rewards', [ConsumerRewardController::class, 'index'])->name('rewards.index');
        Route::post('/rewards/{rewardItem}', [ConsumerRewardController::class, 'redeem'])->name('rewards.redeem');
        Route::get('/wardrobe', ConsumerWardrobeController::class)->name('wardrobe');
        Route::get('/claims', [ConsumerClaimController::class, 'index'])->name('claims.index');
        Route::post('/claims', [ConsumerClaimController::class, 'store'])->name('claims.store');
        Route::get('/notifications', [ConsumerNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [ConsumerNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [ConsumerNotificationController::class, 'read'])->name('notifications.read');
        Route::get('/my-data', ConsumerDataExportController::class)->middleware('throttle:3,1')->name('data.export');
    });

    Route::middleware('role:consumer,admin')->group(function () {
        Route::resource('scans', ScanController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::post('/scans/{scan}/confirm/{product}', [ScanController::class, 'confirm'])->name('scans.confirm');
        Route::post('/scans/{scan}/confirm-data', [ScanController::class, 'confirmData'])->name('scans.confirm-data');
        Route::patch('/scans/{scan}/supplement-data', [ScanController::class, 'supplementData'])->name('scans.supplement-data');
        Route::post('/scans/{scan}/reanalyse', [ScanController::class, 'reanalyse'])->name('scans.reanalyse');
    });
    Route::middleware('role:consumer,admin')->group(function () {
        Route::resource('product-returns', ProductReturnController::class);
        Route::post('/product-returns/{productReturn}/declare-deposit', [ProductReturnController::class, 'declareDeposit'])->middleware('role:consumer')->name('product-returns.declare-deposit');
        Route::post('/material-transfers/{materialMatch}', [ConsumerMaterialTransferController::class, 'store'])->middleware('role:consumer')->name('consumer.material-transfers.store');
    });
    Route::middleware('role:collector,admin')->prefix('collector')->name('collector.')->group(function () {
        Route::post('/material-matches/{materialMatch}/transfer', [CollectorReturnController::class, 'transfer'])->name('material-transfers.store');
        Route::get('/returns', [CollectorReturnController::class, 'index'])->name('returns.index');
        Route::patch('/returns/{productReturn}', [CollectorReturnController::class, 'update'])->name('returns.update');
        Route::post('/trade-ins/{tradeIn}/receive', [CollectorReturnController::class, 'receiveTradeIn'])->name('trade-ins.receive');
    });
    Route::middleware('role:admin,brand')->get('/material-usage-proofs/{event}/{index}', [BrandMaterialUsageController::class, 'proof'])->whereNumber('index')->name('material-usage.proof');
    Route::middleware('role:admin,brand')->get('/evidences/{evidence}/download', EvidenceDownloadController::class)->name('evidences.download');
    Route::middleware('role:admin,brand')->get('/roadmap-proofs/{submission}/{index}', RoadmapProofController::class)->whereNumber('index')->name('roadmaps.proofs.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('repair-partners', RepairPartnerController::class);
    Route::resource('repair-workshops', RepairWorkshopController::class);
});

Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
Route::get('/partners/{repairPartner:slug}', [PartnerController::class, 'show'])->name('partners.show');
Route::get('/public-workshops', [RepairWorkshopController::class, 'catalog'])->name('public-workshops.index');
Route::get('/public-workshops/{repairWorkshop:slug}', [RepairWorkshopController::class, 'showPublic'])->name('public-workshops.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{article:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{article:slug}/react', ArticleReactionController::class)->middleware('throttle:30,1')->name('blog.react');

Route::post('/assistant-chat/ask', [ChatWidgetController::class, 'ask'])->middleware('throttle:20,1')->name('chatbot.ask');
Route::get('/assistant-chat/suggestions', [ChatWidgetController::class, 'suggestions'])->middleware('throttle:30,1')->name('chatbot.suggestions');

require __DIR__.'/auth.php';
