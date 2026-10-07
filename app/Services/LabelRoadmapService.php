<?php

namespace App\Services;

use App\Models\Label;
use App\Models\LabelRoadmap;
use App\Models\MaterialMatch;
use App\Models\Product;
use App\Models\RoadmapStep;
use App\Models\RoadmapSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRoadmapService
{
    public function snapshot(Product $p): array
    {
        $p->loadMissing('materials');

        return ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'category' => $p->category, 'current_version' => $p->current_version,
            'composition' => $p->materials->map(fn ($m) => ['material' => $m->name, 'percentage' => $m->pivot->percentage, 'supplier' => $m->pivot->supplier])->all()];
    }

    public function create(User $user, array $data): LabelRoadmap
    {
        return DB::transaction(function () use ($user, $data) {
            $label = Label::findOrFail($data['label_id']);
            $roadmap = LabelRoadmap::create(collect($data)->except('products')->all() + ['brand_id' => $user->brand->id, 'label_snapshot' => $label->only('id', 'name', 'official_url', 'description')]);
            $this->setProducts($roadmap, $data['products']);
            $definitions = app(RoadmapTemplates::class)->steps($label);
            $days = $roadmap->starts_on->diffInDays($roadmap->target_date);
            foreach ($definitions as $index => [$title,$instructions,$evidence]) {
                $roadmap->steps()->create(['title' => $title, 'instructions' => $instructions, 'required_evidence' => $evidence,
                    'position' => $index + 1, 'due_date' => $roadmap->starts_on->copy()->addDays((int) round($days * ($index + 1) / count($definitions))),
                    'owner_name' => $user->name, 'goal' => $instructions, 'status' => 'todo']);
            }
            app(ActivityService::class)->log($user->id, $roadmap->brand_id, 'roadmap.created', $roadmap, ['scope_version' => 1, 'products' => $data['products']]);

            return $roadmap;
        });
    }

    private function setProducts(LabelRoadmap $roadmap, array $ids): void
    {
        $products = Product::with('materials')->where('brand_id', $roadmap->brand_id)->whereIn('id', $ids)->get();
        if ($products->count() !== count($ids)) {
            throw ValidationException::withMessages(['products' => 'Sélectionnez uniquement des produits disponibles de votre marque.']);
        }
        $roadmap->products()->sync($products->mapWithKeys(fn ($p) => [$p->id => ['snapshot' => json_encode($this->snapshot($p), JSON_UNESCAPED_UNICODE)]])->all());
    }

    public function update(User $user, LabelRoadmap $roadmap, array $data): void
    {
        DB::transaction(function () use ($user, $roadmap, $data) {
            $roadmap = LabelRoadmap::whereKey($roadmap->id)->lockForUpdate()->firstOrFail();
            abort_unless($roadmap->status === 'active', 409);
            $before = $roadmap->products()->pluck('products.id')->sort()->values()->all();
            $after = collect($data['products'])->map(fn ($v) => (int) $v)->sort()->values()->all();
            $changed = $before !== $after || $roadmap->scope_description !== $data['scope_description'] || $roadmap->baseline !== $data['baseline'] || $roadmap->objective !== $data['objective'];
            $roadmap->update(collect($data)->except('products')->all());
            foreach ($roadmap->steps()->get() as $step) {
                if ($step->due_date->lt($roadmap->starts_on)) {
                    $step->update(['due_date' => $roadmap->starts_on]);
                } elseif ($step->due_date->gt($roadmap->target_date)) {
                    $step->update(['due_date' => $roadmap->target_date]);
                }
            }
            if ($changed) {
                $roadmap->increment('scope_version');
                $this->setProducts($roadmap, $data['products']);
                $roadmap->steps()->update(['status' => 'todo']);
            }
            app(ActivityService::class)->log($user->id, $roadmap->brand_id, 'roadmap.updated', $roadmap, ['scope_version' => $roadmap->scope_version, 'approvals_reset' => $changed]);
        });
    }

    public function submit(User $user, RoadmapStep $step, array $data, array $files): RoadmapSubmission
    {
        return DB::transaction(function () use ($user, $step, $data, $files) {
            $roadmap = LabelRoadmap::whereKey($step->label_roadmap_id)->lockForUpdate()->firstOrFail();
            $step = RoadmapStep::whereKey($step->id)->lockForUpdate()->firstOrFail();
            abort_unless($roadmap->status === 'active', 409);
            $products = $roadmap->products()->get();
            abort_unless((int) $data['scope_version'] === $roadmap->scope_version, 409, 'Le périmètre a changé. Rechargez la feuille de route.');
            foreach ($products as $product) {
                if (! filled($data['product_results'][$product->id] ?? null)) {
                    throw ValidationException::withMessages(['product_results' => 'Décrivez le résultat concret pour chaque produit du périmètre.']);
                }
            }
            $material = null;
            if (! empty($data['material_match_id'])) {
                $match = MaterialMatch::with('productReturn')->whereHas('request', fn ($q) => $q->where('brand_id', $roadmap->brand_id))->where('status', 'verified')->findOrFail($data['material_match_id']);
                $material = ['match_id' => $match->id, 'return_reference' => $match->productReturn->reference, 'verified_collected_kg' => $match->productReturn->confirmed_weight_kg ?? $match->productReturn->weight_kg,
                    'material_name' => $data['material_name'] ?? null, 'recycled_output_kg' => $data['recycled_output_kg'] ?? null, 'incorporated_kg' => $data['incorporated_kg'] ?? null];
                $input = (float) $material['verified_collected_kg'];
                if (isset($material['recycled_output_kg']) && (float) $material['recycled_output_kg'] > $input) {
                    throw ValidationException::withMessages(['recycled_output_kg' => 'La sortie attribuée à ce lot ne peut pas dépasser son poids collecté.']);
                }
                if (isset($material['incorporated_kg']) && (! isset($material['recycled_output_kg']) || (float) $material['incorporated_kg'] > (float) $material['recycled_output_kg'])) {
                    throw ValidationException::withMessages(['incorporated_kg' => 'Le poids incorporé ne peut pas dépasser la sortie recyclée documentée.']);
                }
            } elseif (isset($data['recycled_output_kg']) || isset($data['incorporated_kg'])) {
                throw ValidationException::withMessages(['material_match_id' => 'Associez un lot vérifié pour enregistrer ces poids.']);
            }
            $submission = $step->submissions()->create(['submitted_by' => $user->id, 'scope_version' => $roadmap->scope_version,
                'version' => ($step->submissions()->max('version') ?? 0) + 1, 'actions' => $data['actions'], 'results' => $data['results'],
                'product_results' => collect($data['product_results'])->only($products->pluck('id')->all())->all(),
                'files' => $files, 'scope_snapshot' => $products->map(fn ($p) => json_decode($p->pivot->snapshot, true))->all(),
                'step_snapshot' => $step->only('title', 'goal', 'due_date', 'required_evidence'), 'material_match_id' => $data['material_match_id'] ?? null, 'material_record' => $material, 'status' => 'pending']);
            $step->update(['status' => 'submitted']);
            app(ActivityService::class)->log($user->id, $roadmap->brand_id, 'roadmap.step.submitted', $submission, ['scope_version' => $roadmap->scope_version]);

            return $submission;
        });
    }

    public function review(User $admin, RoadmapSubmission $submission, array $data): void
    {
        DB::transaction(function () use ($admin, $submission, $data) {
            $roadmap = LabelRoadmap::whereKey($submission->step->label_roadmap_id)->lockForUpdate()->firstOrFail();
            $step = RoadmapStep::whereKey($submission->roadmap_step_id)->lockForUpdate()->firstOrFail();
            $submission = RoadmapSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            abort_unless($roadmap->status === 'active' && $step->status === 'submitted' && $submission->status === 'pending' && $submission->scope_version === $roadmap->scope_version
             && (int) $step->submissions()->max('version') === $submission->version, 409, 'Cette soumission est ancienne ou déjà examinée.');
            $submission->update($data + ['reviewed_by' => $admin->id, 'reviewed_at' => now()]);
            $step->update(['status' => $data['status'] === 'approved' ? 'approved' : 'changes_requested']);
            app(ActivityService::class)->log($admin->id, $roadmap->brand_id, 'roadmap.step.'.$data['status'], $submission, ['comment' => $data['review_comment']]);
            app(ActivityService::class)->notify($roadmap->brand->user_id,'roadmap_review','Revue de feuille de route',$step->title.' : '.$data['review_comment'],route('brand.roadmaps.show',$roadmap));
        });
    }
}
