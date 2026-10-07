<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelRoadmap;
use App\Models\MaterialMatch;
use App\Models\RoadmapStep;
use App\Services\ActivityService;
use App\Services\LabelRoadmapService;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BrandRoadmapController extends Controller
{
    private function own(Request $r, LabelRoadmap $roadmap): void
    {
        abort_unless($roadmap->brand_id === $r->user()->brand->id, 403);
    }

    private function labels(Request $r)
    {
        $brand = $r->user()->brand;

        return $brand->application()->exists() ? $brand->approvedLabels()->where('is_active', true)->get() : Label::where('is_active', true)->get();
    }

    public function index(Request $r)
    {
        return view('brand.roadmaps.index', ['roadmaps' => LabelRoadmap::where('brand_id', $r->user()->brand->id)->with('label', 'products', 'steps')->latest()->paginate(15)]);
    }

    public function create(Request $r)
    {
        return view('brand.roadmaps.form', ['roadmap' => new LabelRoadmap, 'labels' => $this->labels($r), 'products' => $r->user()->brand->products()->with('materials')->orderBy('name')->get(), 'selectedLabel' => $r->integer('label_id')]);
    }

    private function validated(Request $r, bool $creating): array
    {
        $rules = ['name' => 'required|string|max:255', 'scope_description' => 'required|string|max:3000', 'baseline' => 'required|string|max:5000', 'objective' => 'required|string|max:5000',
            'starts_on' => 'required|date', 'target_date' => 'required|date|after_or_equal:starts_on', 'products' => 'required|array|min:1|max:50',
            'products.*' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('brand_id', $r->user()->brand->id)->whereNull('deleted_at')]];
        if ($creating) {
            $rules['label_id'] = ['required', 'integer', Rule::in($this->labels($r)->pluck('id')->all())];
        }

        return $r->validate($rules);
    }

    public function store(Request $r, LabelRoadmapService $service)
    {
        $roadmap = $service->create($r->user(), $this->validated($r, true));

        return to_route('brand.roadmaps.show', $roadmap)->with('success', 'Feuille de route créée pour les produits sélectionnés.');
    }

    public function show(Request $r, LabelRoadmap $roadmap)
    {
        $this->own($r, $roadmap);
        $roadmap->load('label', 'products', 'steps.submissions.reviewer');
        $matches = MaterialMatch::with('productReturn.product')->where('status', 'verified')->whereHas('request', fn ($q) => $q->where('brand_id', $roadmap->brand_id))->get();

        return view('brand.roadmaps.show', compact('roadmap', 'matches'));
    }

    public function edit(Request $r, LabelRoadmap $roadmap)
    {
        $this->own($r, $roadmap);
        abort_unless($roadmap->status === 'active', 409);
        $roadmap->load('products');

        return view('brand.roadmaps.form', ['roadmap' => $roadmap, 'labels' => $this->labels($r), 'products' => $r->user()->brand->products()->with('materials')->orderBy('name')->get(), 'selectedLabel' => $roadmap->label_id]);
    }

    public function update(Request $r, LabelRoadmap $roadmap, LabelRoadmapService $service)
    {
        $this->own($r, $roadmap);
        $service->update($r->user(), $roadmap, $this->validated($r, false));

        return to_route('brand.roadmaps.show', $roadmap)->with('success', 'Plan mis à jour. Un changement de périmètre ou d’objectif remet les validations à zéro.');
    }

    public function destroy(Request $r, LabelRoadmap $roadmap)
    {
        $this->own($r, $roadmap);
        DB::transaction(function () use ($r, $roadmap) {
            $roadmap = LabelRoadmap::whereKey($roadmap->id)->lockForUpdate()->firstOrFail();
            $roadmap->update(['status' => 'archived']);
            app(ActivityService::class)->log($r->user()->id, $roadmap->brand_id, 'roadmap.archived', $roadmap);
        });

        return to_route('brand.roadmaps.index')->with('success', 'Feuille de route archivée. Historique conservé.');
    }

    public function restore(Request $r, LabelRoadmap $roadmap)
    {
        $this->own($r, $roadmap);
        DB::transaction(function () use ($r, $roadmap) {
            $roadmap = LabelRoadmap::whereKey($roadmap->id)->lockForUpdate()->firstOrFail();
            $roadmap->update(['status' => 'active']);
            app(ActivityService::class)->log($r->user()->id, $roadmap->brand_id, 'roadmap.restored', $roadmap);
        });

        return to_route('brand.roadmaps.show', $roadmap)->with('success', 'Feuille de route restaurée.');
    }

    public function step(Request $r, RoadmapStep $step)
    {
        $roadmap = $step->roadmap;
        $this->own($r, $roadmap);
        $data = $r->validate(['goal' => 'required|string|max:4000', 'owner_name' => 'required|string|max:255', 'due_date' => ['required', 'date', 'after_or_equal:'.$roadmap->starts_on->format('Y-m-d'), 'before_or_equal:'.$roadmap->target_date->format('Y-m-d')],
            'status' => 'required|in:todo,in_progress']);
        DB::transaction(function () use ($r, $step, $data) {
            $roadmap = LabelRoadmap::whereKey($step->label_roadmap_id)->lockForUpdate()->firstOrFail();
            abort_unless($roadmap->status === 'active', 409);
            $step = RoadmapStep::whereKey($step->id)->lockForUpdate()->firstOrFail();
            $step->update($data);
            app(ActivityService::class)->log($r->user()->id, $roadmap->brand_id, 'roadmap.step.updated', $step);
        });

        return back()->with('success', 'Objectif et échéance mis à jour. Soumettez les preuves pour validation.');
    }

    public function submit(Request $r, RoadmapStep $step, LabelRoadmapService $service, MediaStorageService $media)
    {
        $roadmap = $step->roadmap;
        $this->own($r, $roadmap);
        abort_unless($roadmap->status === 'active', 409);
        $rules = ['scope_version' => ['required', 'integer', Rule::in([$roadmap->scope_version])],
            'actions' => 'required|string|min:10|max:5000', 'results' => 'required|string|min:10|max:5000',
            'product_results' => 'required|array', 'product_results.*' => 'required|string|min:5|max:2000',
            'proofs' => 'required|array|min:1|max:4', 'proofs.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,csv,txt|max:8192',
            'material_match_id' => ['nullable', 'integer', Rule::exists('material_matches', 'id')->where('status', 'verified')],
            'material_name' => 'nullable|required_with:material_match_id|string|max:255',
            'recycled_output_kg' => 'nullable|numeric|min:0|max:100000', 'incorporated_kg' => 'nullable|numeric|min:0|max:100000'];
        foreach ($roadmap->products()->pluck('products.id') as $id) {
            $rules['product_results.'.$id] = 'required|string|min:5|max:2000';
        }
        $data = $r->validate($rules);
        $files = [];
        try {
            foreach ($r->file('proofs') as $file) {
                $files[] = $media->storeRoadmapProof($file, $roadmap->id);
            }
            $service->submit($r->user(), $step, $data, $files);
        } catch (\Throwable $e) {
            foreach ($files as $file) {
                try {
                    $media->deleteRoadmapProof($file);
                } catch (\Throwable $cleanup) {
                    report($cleanup);
                }
            }throw $e;
        }

        return back()->with('success','Résultats et preuves soumis. L’administrateur doit les vérifier.');
    }
}
