<?php

namespace App\Http\Requests;

use App\Models\RoadmapStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Contrôles de saisie PHP exécutés côté serveur pour la remise d'une tâche de label.
 */
class SubmitRoadmapStepRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /** @var RoadmapStep|null $step */
        $step = $this->route('step');

        abort_unless($step?->roadmap?->status === 'active', 409, 'Cette feuille de route est archivée et n’accepte plus de soumission.');
    }

    public function authorize(): bool
    {
        /** @var RoadmapStep|null $step */
        $step = $this->route('step');

        if ($step === null || ! $this->user()) {
            return false;
        }

        $user = $this->user();

        return ($user->isBrand() && $step->roadmap->brand_id === $user->brand?->id)
            || ($step->brand_member_id === $user->brandMembership?->id);
    }

    public function rules(): array
    {
        /** @var RoadmapStep $step */
        $step = $this->route('step');
        $roadmap = $step->roadmap;
        $rules = [
            'scope_version' => ['required', 'integer', Rule::in([$roadmap->scope_version])],
            'actions' => ['required', 'string', 'min:10', 'max:5000'],
            'results' => ['required', 'string', 'min:10', 'max:5000'],
            'product_results' => ['required', 'array'],
            'proofs' => ['required', 'array', 'min:1', 'max:4'],
            'proofs.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,csv,txt', 'max:8192'],
            'material_match_id' => ['nullable', 'integer', Rule::exists('material_matches', 'id')->where('status', 'verified')],
            'material_name' => ['nullable', 'string', 'max:255'],
            'recycled_output_kg' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'incorporated_kg' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];

        foreach ($roadmap->products()->pluck('products.id') as $productId) {
            $rules['product_results.'.$productId] = ['required', 'string', 'min:5', 'max:2000'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'scope_version.in' => 'La feuille de route a été modifiée. Rechargez la page avant de soumettre votre travail.',
            'actions.min' => 'Décrivez les actions réalisées avec au moins 10 caractères.',
            'results.min' => 'Décrivez les résultats obtenus avec au moins 10 caractères.',
            'product_results.*.required' => 'Un résultat est obligatoire pour chaque produit concerné.',
            'proofs.required' => 'Ajoutez au moins une preuve.',
            'proofs.max' => 'Vous pouvez joindre au maximum 4 preuves.',
            'proofs.*.mimes' => 'Chaque preuve doit être un PDF, une image, un document Word, un CSV ou un fichier texte.',
            'proofs.*.max' => 'Chaque preuve ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'actions' => 'actions réalisées', 'results' => 'résultats et écarts',
            'product_results' => 'résultats par produit', 'proofs' => 'preuves',
            'proofs.*' => 'preuve', 'material_match_id' => 'retour de matière',
            'material_name' => 'matière', 'recycled_output_kg' => 'quantité recyclée',
            'incorporated_kg' => 'quantité incorporée',
        ];
    }
}
