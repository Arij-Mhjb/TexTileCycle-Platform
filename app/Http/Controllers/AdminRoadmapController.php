<?php

namespace App\Http\Controllers;

use App\Models\LabelRoadmap;
use App\Models\RoadmapSubmission;
use App\Services\LabelRoadmapService;
use Illuminate\Http\Request;

class AdminRoadmapController extends Controller
{
    public function index(Request $request)
    {
        $query = LabelRoadmap::with('brand', 'label', 'products', 'recommendation', 'steps.submissions');
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")
                ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%"))
                ->orWhereHas('label', fn ($label) => $label->where('name', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pendingSubmissions = RoadmapSubmission::where('status', 'pending')
            ->with('submitter', 'step.roadmap.brand', 'step.roadmap.label')
            ->latest()->limit(50)->get()
            ->filter(fn ($submission) => $submission->step->roadmap->status === 'active'
                && $submission->scope_version === $submission->step->roadmap->scope_version)
            ->take(10)->values();

        return view('admin.roadmaps.index', [
            'roadmaps' => $query->latest()->paginate(20)->withQueryString(),
            'pendingSubmissions' => $pendingSubmissions,
            'pendingReviewCount' => RoadmapSubmission::where('status', 'pending')->count(),
            'activeCount' => LabelRoadmap::where('status', 'active')->count(),
            'brandCount' => LabelRoadmap::distinct('brand_id')->count('brand_id'),
        ]);
    }

    public function show(LabelRoadmap $roadmap)
    {
        $roadmap->load('brand', 'label', 'products', 'recommendation', 'steps.submissions.reviewer',
            'steps.submissions.submitter', 'materialUsages.match.productReturn', 'materialUsages.events');

        return view('admin.roadmaps.show', compact('roadmap'));
    }

    public function review(Request $r, RoadmapSubmission $submission, LabelRoadmapService $service)
    {
        $data = $r->validate(['status' => 'required|in:approved,rejected', 'review_comment' => 'required|string|max:4000']);
        $service->review($r->user(), $submission, $data);

        return back()->with('success', 'Décision enregistrée et marque notifiée.');
    }
}
