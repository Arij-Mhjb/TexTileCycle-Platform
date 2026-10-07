<?php

namespace App\Http\Controllers;

use App\Models\RoadmapSubmission;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;

class RoadmapProofController extends Controller
{
    public function __invoke(Request $r, RoadmapSubmission $submission, int $index, MediaStorageService $media)
    {
        abort_unless($r->user()->isAdmin() || ($r->user()->isBrand() && $submission->step->roadmap->brand_id === $r->user()->brand?->id), 403);
        abort_unless(isset($submission->files[$index]), 404);
        $file = $submission->files[$index];
        $content = $media->readRoadmapProof($file);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);

        return response()->streamDownload(fn () => print ($content), $filename, ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
