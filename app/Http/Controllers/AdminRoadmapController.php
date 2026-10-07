<?php

namespace App\Http\Controllers;

use App\Models\LabelRoadmap;
use App\Models\RoadmapSubmission;
use App\Services\LabelRoadmapService;
use Illuminate\Http\Request;

class AdminRoadmapController extends Controller
{
    public function index()
    {
        return view('admin.roadmaps.index', ['roadmaps' => LabelRoadmap::with('brand', 'label', 'products', 'steps')->latest()->paginate(20)]);
    }

    public function show(LabelRoadmap $roadmap)
    {
        $roadmap->load('brand', 'label', 'products', 'steps.submissions.reviewer', 'steps.submissions.submitter', 'materialUsages.match.productReturn', 'materialUsages.events');

        return view('admin.roadmaps.show', compact('roadmap'));
    }

    public function review(Request $r, RoadmapSubmission $submission, LabelRoadmapService $service)
    {
        $data = $r->validate(['status' => 'required|in:approved,rejected', 'review_comment' => 'required|string|max:4000']);
        $service->review($r->user(), $submission, $data);

        return back()->with('success', 'Décision enregistrée et marque notifiée.');
    }
}
