<?php

namespace App\Services;

use App\Models\ConsumerNotification;
use App\Models\LabelRoadmap;
use App\Models\MaterialMatch;
use App\Models\MaterialUsage;
use App\Models\RoadmapStep;
use App\Models\RoadmapSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialUsageService
{
    public function initialize(MaterialMatch $match): MaterialUsage
    {
        $usage = MaterialUsage::firstOrCreate(['material_match_id' => $match->id], [
            'status' => 'stored', 'input_kg' => $match->productReturn->confirmed_weight_kg,
        ]);
        $step = $match->request->roadmapStep;
        if ($step && $step->roadmap->status === 'active' && $step->roadmap->scope_version === (int) $match->request->roadmap_scope_version) {
            $this->bind($match, $step->roadmap);
        }

        return $usage->fresh();
    }

    // One physical return has one ledger and one product scope. Multiple evidence
    // references within this scope never create additional kilograms.
    public function bind(MaterialMatch $match, LabelRoadmap $roadmap): MaterialUsage
    {
        $match = MaterialMatch::whereKey($match->id)->lockForUpdate()->firstOrFail();
        if ($match->status !== 'verified' || $match->request->brand_id !== $roadmap->brand_id || $roadmap->status !== 'active') {
            throw ValidationException::withMessages(['material_match_id' => 'Choisissez une matière validée de votre marque et une feuille de route active.']);
        }
        $usage = MaterialUsage::firstOrCreate(['material_match_id' => $match->id], [
            'status' => 'stored', 'input_kg' => $match->productReturn->confirmed_weight_kg,
        ]);
        if ($usage->label_roadmap_id && ($usage->label_roadmap_id !== $roadmap->id || $usage->scope_version !== $roadmap->scope_version)) {
            throw ValidationException::withMessages(['material_match_id' => 'Cette matière est déjà affectée à un autre périmètre. Elle ne peut pas être comptée une seconde fois.']);
        }
        // Account for pre-migration evidence, including archived plans and old scopes.
        $foreign = RoadmapSubmission::where('material_match_id', $match->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(fn ($q) => $q->where('scope_version', '!=', $roadmap->scope_version)
                ->orWhereHas('step', fn ($step) => $step->where('label_roadmap_id', '!=', $roadmap->id)))->exists();
        if ($foreign) {
            throw ValidationException::withMessages(['material_match_id' => 'Cette matière figure déjà dans les preuves d’une autre feuille de route.']);
        }
        if ($usage->input_kg !== null && ($match->productReturn->confirmed_weight_kg === null
            || abs((float) $usage->input_kg - (float) $match->productReturn->confirmed_weight_kg) > 0.001)) {
            throw ValidationException::withMessages(['material_match_id' => 'La pesée confirmée diffère du bilan affecté. Faites vérifier cette incohérence.']);
        }
        $usage->update(['label_roadmap_id' => $roadmap->id, 'scope_version' => $roadmap->scope_version,
            'input_kg' => $usage->input_kg ?? $match->productReturn->confirmed_weight_kg]);

        return $usage->fresh();
    }

    public function advance(User $actor, MaterialMatch $match, array $data, array $files): MaterialUsage
    {
        return DB::transaction(function () use ($actor, $match, $data, $files) {
            $existing = MaterialUsage::where('material_match_id', $match->id)->firstOrFail();
            $roadmap = LabelRoadmap::whereKey($existing->label_roadmap_id)->lockForUpdate()->firstOrFail();
            $usage = $this->bind($match, $roadmap);
            abort_unless($roadmap->brand->user_id === $actor->id, 403);
            $next = ['stored' => 'sent', 'sent' => 'processed', 'processed' => 'incorporated'][$usage->status] ?? null;
            $correction = (bool) ($data['correction'] ?? false);
            if ($correction && $usage->status !== 'stored' && $data['status'] === $usage->status) {
                $next = $usage->status;
            } elseif ($next !== $data['status'] || $correction) {
                throw ValidationException::withMessages(['status' => 'Suivez l’ordre : stock, transfert, transformation, incorporation.']);
            }
            $input = $match->productReturn->confirmed_weight_kg;
            if ($input === null || (float) $input <= 0) {
                throw ValidationException::withMessages(['status' => 'Une pesée confirmée par le collecteur ou l’administrateur est nécessaire.']);
            }
            if ($usage->input_kg !== null && abs((float) $usage->input_kg - (float) $input) > 0.001) {
                throw ValidationException::withMessages(['status' => 'La pesée a changé depuis l’affectation. Faites vérifier cette incohérence avant de poursuivre.']);
            }
            $updates = ['status' => $next, 'input_kg' => $input];
            if ($next === 'processed' || ($correction && $next === 'incorporated')) {
                $output = (float) ($data['recycled_output_kg'] ?? -1);
                if ($output < 0 || $output > (float) $input) {
                    throw ValidationException::withMessages(['recycled_output_kg' => 'La sortie recyclée doit être renseignée et ne peut pas dépasser la pesée confirmée.']);
                }
                $updates['recycled_output_kg'] = $output;
            }
            if ($next === 'incorporated') {
                $quantities = collect($data['product_quantities'] ?? []);
                $ids = $roadmap->products()->pluck('products.id')->map(fn ($id) => (string) $id);
                if ($quantities->isEmpty() || $quantities->keys()->map(fn ($id) => (string) $id)->diff($ids)->isNotEmpty()) {
                    throw ValidationException::withMessages(['product_quantities' => 'Répartissez la matière uniquement entre les produits du périmètre.']);
                }
                $total = round($quantities->sum(), 2);
                if ($total <= 0 || $total > (float) ($updates['recycled_output_kg'] ?? $usage->recycled_output_kg)) {
                    throw ValidationException::withMessages(['product_quantities' => 'Le total incorporé doit être positif et ne peut pas dépasser la sortie recyclée.']);
                }
                $updates += ['incorporated_kg' => $total, 'product_quantities' => $quantities->all()];
            }
            if ($correction) {
                $submissions = RoadmapSubmission::where('material_match_id', $match->id)
                    ->where('scope_version', $roadmap->scope_version)->whereHas('step', fn ($q) => $q->where('label_roadmap_id', $roadmap->id))->get();
                foreach ($submissions->groupBy('roadmap_step_id') as $versions) {
                    $latest = $versions->sortByDesc('version')->first();
                    if ((int) $latest->step->submissions()->max('version') !== $latest->version) {
                        continue;
                    }
                    // Preserve reviewed history, but require a fresh review of changed facts.
                    $latest->step->update(['status' => 'changes_requested']);
                    if ($latest->status === 'pending') {
                        $latest->update(['status' => 'rejected', 'review_comment' => 'Bilan matière corrigé : une nouvelle soumission est nécessaire.', 'reviewed_at' => now()]);
                    }
                }
            }
            $usage->update($updates);
            $usage->events()->create(['user_id' => $actor->id, 'status' => $next, 'note' => $data['note'], 'files' => $files,
                'measurements' => $usage->only('input_kg', 'recycled_output_kg', 'incorporated_kg', 'product_quantities') + ['correction' => $correction]]);
            app(ActivityService::class)->log($actor->id, $roadmap->brand_id, 'material.'.$next, $usage);
            ConsumerNotification::create(['user_id' => $match->productReturn->user_id, 'type' => 'material_'.$next,
                'title' => 'Seconde vie de votre textile', 'message' => $usage->statusLabel().' : justificatifs enregistrés par la marque.',
                'url' => route('product-returns.show', $match->productReturn)]);

            return $usage->fresh();
        });
    }

    public function evidence(MaterialMatch $match, LabelRoadmap $roadmap, RoadmapStep $step, array $data): array
    {
        if (! $step->material_stage) {
            throw ValidationException::withMessages(['material_match_id' => 'Associez cette matière à une étape de collecte, de transformation ou d’incorporation.']);
        }
        $usage = $this->bind($match, $roadmap);
        $levels = ['stored' => 0, 'sent' => 1, 'processed' => 2, 'incorporated' => 3];
        $required = ['collection' => 0, 'transformation' => 2, 'incorporation' => 3][$step->material_stage];
        if (($levels[$usage->status] ?? -1) < $required || $usage->input_kg === null || (float) $usage->input_kg <= 0) {
            throw ValidationException::withMessages(['material_match_id' => 'Documentez d’abord la pesée et la seconde vie requise pour cette étape.']);
        }
        // Quantities come from the unique ledger, never from another declaration.
        foreach (['recycled_output_kg', 'incorporated_kg'] as $key) {
            if (isset($data[$key]) && ($usage->$key === null || abs((float) $data[$key] - (float) $usage->$key) > 0.001)) {
                throw ValidationException::withMessages([$key => 'Cette quantité doit correspondre au bilan documenté de la matière.']);
            }
        }

        return ['usage_id' => $usage->id, 'match_id' => $match->id, 'return_reference' => $match->productReturn->reference,
            'verified_collected_kg' => $usage->input_kg, 'material_name' => $match->request->material_name,
            'recycled_output_kg' => $usage->recycled_output_kg, 'incorporated_kg' => $usage->incorporated_kg,
            'product_quantities' => $usage->product_quantities, 'lifecycle_status' => $usage->status,
            'event_ids' => $usage->events()->pluck('id')->all()];
    }
}
