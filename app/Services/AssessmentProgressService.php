<?php

namespace App\Services;

use App\Models\Assessment;

class AssessmentProgressService
{
    public function refresh(Assessment $assessment): Assessment
    {
        $assessment->loadMissing('label.steps', 'evidences');
        $total = $assessment->label?->steps->count() ?? 0;
        $approved = $assessment->evidences
            ->where('status', 'approved')
            ->pluck('label_step_id')->filter()->unique()->count();
        $score = $total > 0 ? (int) round(($approved / $total) * 100) : 0;
        $assessment->update([
            'score' => $score,
            'readiness_score' => $score,
            'status' => $total > 0 && $approved === $total ? 'verified_evidence' : ($assessment->submitted_at ? 'in_review' : 'draft'),
        ]);
        return $assessment->fresh();
    }
}
