<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Services\AssessmentProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminEvidenceReviewController extends Controller
{
    public function update(Request $request, Evidence $evidence): RedirectResponse
    {
        $data = $request->validate(['status'=>'required|in:approved,rejected','admin_comment'=>'nullable|string|max:3000']);
        $evidence->update($data + ['reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);
        app(AssessmentProgressService::class)->refresh($evidence->assessment);
        return back()->with('success', 'Décision enregistrée.');
    }
}
