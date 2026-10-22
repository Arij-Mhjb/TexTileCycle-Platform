<?php

namespace App\Http\Controllers;

use App\Models\MaterialMatch;
use App\Models\MaterialUsageEvent;
use App\Models\RoadmapStep;
use App\Services\MaterialUsageService;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandMaterialUsageController extends Controller
{
    private function own(Request $request, MaterialMatch $match): void
    {
        abort_unless($match->request->brand_id === $request->user()->brand?->id, 403);
        abort_unless($match->status === 'verified', 409);
    }

    public function assign(Request $request, MaterialMatch $materialMatch, MaterialUsageService $service)
    {
        $this->own($request, $materialMatch);
        $data = $request->validate(['roadmap_step_id' => 'required|integer|exists:roadmap_steps,id']);
        $step = RoadmapStep::findOrFail($data['roadmap_step_id']);
        abort_unless($step->roadmap->brand_id === $request->user()->brand->id, 403);
        abort_unless($step->material_stage === 'collection', 422);
        DB::transaction(function () use ($materialMatch, $step, $service) {
            $roadmap = $step->roadmap()->lockForUpdate()->firstOrFail();
            $service->bind($materialMatch, $roadmap);
        });

        return to_route('brand.material-matches.index')->with('success', 'Matière affectée à la feuille de route. Aucun score n’est attribué avant la revue des preuves.');
    }

    public function update(Request $request, MaterialMatch $materialMatch, MaterialUsageService $service, MediaStorageService $media)
    {
        $this->own($request, $materialMatch);
        $usage = $materialMatch->usage;
        abort_unless($usage?->label_roadmap_id, 409, 'Associez d’abord une feuille de route.');
        $data = $request->validate(['correction' => 'nullable|boolean', 'status' => 'required|in:sent,processed,incorporated', 'note' => 'required|string|min:10|max:4000',
            'recycled_output_kg' => 'required_if:status,processed|nullable|numeric|min:0|max:999999|decimal:0,2',
            'product_quantities' => 'required_if:status,incorporated|nullable|array',
            'product_quantities.*' => 'required|numeric|min:0|max:999999|decimal:0,2',
            'proofs' => 'required|array|min:1|max:4', 'proofs.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,csv,txt|max:8192']);
        if ($request->boolean('correction') && $data['status'] === 'incorporated') {
            $request->validate(['recycled_output_kg' => 'required|numeric|min:0|max:999999|decimal:0,2']);
        }
        $files = [];
        try {
            foreach ($request->file('proofs') as $file) {
                $files[] = $media->storeRoadmapProof($file, $usage->label_roadmap_id);
            }
            $service->advance($request->user(), $materialMatch, $data, $files);
        } catch (\Throwable $error) {
            foreach ($files as $file) {
                try {
                    $media->deleteRoadmapProof($file);
                } catch (\Throwable $cleanup) {
                    report($cleanup);
                }
            }
            throw $error;
        }

        return back()->with('success', 'Seconde vie documentée. Soumettez ensuite les preuves de l’étape pour la revue administrative.');
    }

    public function proof(Request $request, MaterialUsageEvent $event, int $index, MediaStorageService $media)
    {
        abort_unless($request->user()->isAdmin() || $event->usage->match->request->brand_id === $request->user()->brand?->id, 403);
        $file = $event->files[$index] ?? null;
        abort_unless($file, 404);

        return response($media->readRoadmapProof($file), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace(['"', "\r", "\n"], '', basename($file['name'] ?? 'preuve')).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
