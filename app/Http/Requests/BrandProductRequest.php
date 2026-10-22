<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Contrôles de saisie PHP exécutés côté serveur pour créer ou modifier un produit.
 * Ces règles restent obligatoires même si les contrôles HTML/JavaScript sont désactivés.
 */
class BrandProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isBrand();
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'qr_code' => ['nullable', 'string', 'max:255', Rule::unique('products', 'qr_code')->ignore($product?->id)],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'origin' => ['nullable', 'string', 'max:150'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1900'],
            'recyclability_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recycling_instructions' => ['nullable', 'string'],
            'repairable' => ['nullable', 'boolean'],
            'reusable' => ['nullable', 'boolean'],
            'recyclable' => ['nullable', 'boolean'],
            'materials' => ['nullable', 'array'],
            'materials.*' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $materials = $this->materialPercentages();
            $total = round(array_sum($materials), 2);

            if ($materials !== [] && abs($total - 100) > 0.01) {
                $validator->errors()->add('materials', "La composition doit totaliser exactement 100 %. Total actuel : {$total} %.");
            }
        }];
    }

    public function messages(): array
    {
        return [
            'image.uploaded' => 'La photo dépasse la limite autorisée par le serveur. Choisissez une photo de moins de 2 Mo.',
            'image.image' => 'Le fichier choisi doit être une véritable image.',
            'image.mimes' => 'La photo doit être au format JPG, JPEG, PNG ou WebP.',
            'image.max' => 'La photo ne doit pas dépasser 1,9 Mo. Les grandes photos sont normalement optimisées automatiquement.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom du produit', 'sku' => 'référence SKU', 'barcode' => 'code-barres',
            'qr_code' => 'valeur QR', 'category' => 'catégorie', 'image' => 'photo',
            'recyclability_score' => 'score de recyclabilité', 'materials' => 'composition',
            'materials.*' => 'pourcentage de matière',
        ];
    }

    /** @return array<int|string, float|int|string> */
    public function materialPercentages(): array
    {
        return array_filter($this->input('materials', []), fn ($value) => (float) $value > 0);
    }
}
