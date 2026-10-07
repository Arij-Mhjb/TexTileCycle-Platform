<?php

namespace App\Http\Controllers;

use App\Services\ProductCompletenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BrandDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $brand = $request->user()->brand()->withCount('products')->firstOrFail();
        $products = $brand->products()->with('materials')->get();
        foreach ($products as $product) {
            app(ProductCompletenessService::class)->refresh($product);
        }

        $assessments = $request->user()->assessments()->with('label', 'evidences')->latest()->get();
        $roadmaps = $brand->roadmaps()->where('status', 'active')
            ->with(['label', 'products:id,name,sku', 'steps'])
            ->orderBy('target_date')->get();
        $labelPursuits = $this->labelPursuits($roadmaps);
        $nextMilestones = $roadmaps->flatMap(fn ($roadmap) => $roadmap->steps
            ->whereNotIn('status', ['approved'])
            ->map(fn ($step) => ['roadmap' => $roadmap, 'step' => $step]))
            ->sortBy(fn ($item) => $item['step']->due_date?->timestamp ?? PHP_INT_MAX)
            ->take(5)->values();

        $actions = collect();
        foreach ($products as $product) {
            if ($product->completion_score < 100) {
                $actions->push(['level' => 'warning', 'message' => "{$product->name} est complété à {$product->completion_score} %.", 'url' => route('brand.products.edit', $product)]);
            }
            if ($product->status === 'changes_requested') {
                $actions->push(['level' => 'danger', 'message' => "Corrections demandées pour {$product->name}.", 'url' => route('brand.products.edit', $product)]);
            }
        }
        foreach ($assessments as $assessment) {
            if ($assessment->label && $assessment->evidences->where('status', 'rejected')->isNotEmpty()) {
                $actions->push(['level' => 'danger', 'message' => "Une preuve du dossier {$assessment->label->name} est rejetée.", 'url' => route('brand.labels.show', $assessment->label)]);
            }
        }

        return view('brand.dashboard', [
            'brand' => $brand,
            'assessments' => $assessments,
            'products' => $products,
            'roadmaps' => $roadmaps,
            'labelPursuits' => $labelPursuits,
            'nextMilestones' => $nextMilestones,
            'averageRoadmapProgress' => (int) round($roadmaps->avg(fn ($roadmap) => $roadmap->progress()) ?? 0),
            'publishedProducts' => $products->where('status', 'published')->count(),
            'reviewProducts' => $products->where('status', 'review')->count(),
            'draftProducts' => $products->whereIn('status', ['draft', 'changes_requested'])->count(),
            'averageCompletion' => (int) round($products->avg('completion_score') ?? 0),
            'verifiedEvidence' => $assessments->sum(fn ($assessment) => $assessment->evidences->where('status', 'approved')->count()),
            'pendingEvidence' => $assessments->sum(fn ($assessment) => $assessment->evidences->where('status', 'pending')->count()),
            'actions' => $actions->take(8),
            'activities' => $brand->activities()->with('user')->limit(10)->get(),
            'notifications' => $request->user()->brandNotifications()->whereNull('read_at')->latest()->limit(6)->get(),
            'passportProposals' => $brand->passportProposals()->where('status', 'brand_pending')->count(),
        ]);
    }

    private function labelPursuits(Collection $roadmaps): Collection
    {
        return $roadmaps->groupBy('label_id')->map(function (Collection $plans) {
            $first = $plans->first();

            return [
                'label' => $first->label,
                'roadmaps' => $plans,
                'progress' => (int) round($plans->avg(fn ($plan) => $plan->progress())),
                'products' => $plans->flatMap->products->unique('id')->values(),
                'target_date' => $plans->min('target_date'),
                'environmental' => $plans->flatMap(fn ($plan) => $plan->impact_summary['environmental'] ?? [])->filter()->unique()->take(4)->values(),
                'reputation' => $plans->flatMap(fn ($plan) => $plan->impact_summary['reputation'] ?? [])->filter()->unique()->take(4)->values(),
            ];
        })->sortByDesc('progress')->values();
    }
}
