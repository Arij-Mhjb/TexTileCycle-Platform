<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une ligne d'une boucle de recyclage : pièces défectueuses, surplus de matière ou chutes de coupe. */
class RecyclingLoopItem extends Model
{
    public const KINDS = [
        'defective' => 'Pièces défectueuses',
        'surplus' => 'Surplus de matière',
        'offcuts' => 'Chutes de coupe',
    ];

    /** Forme courte, utilisée dans le nom automatique du lot. */
    public const SHORT_KINDS = ['defective' => 'Défectueux', 'surplus' => 'Surplus', 'offcuts' => 'Chutes'];

    protected $fillable = ['recycling_loop_id', 'kind', 'product_id', 'material_id', 'label', 'pieces', 'quantity_kg', 'issue', 'photo_path', 'ai_analysis'];

    protected function casts(): array
    {
        return ['quantity_kg' => 'decimal:2', 'ai_analysis' => 'array'];
    }

    public function loop(): BelongsTo
    {
        return $this->belongsTo(RecyclingLoop::class, 'recycling_loop_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    /** Matière dominante de la ligne : celle choisie, sinon la principale du produit. */
    public function mainMaterial(): ?Material
    {
        return $this->material ?? $this->product?->materials->sortByDesc(fn ($material) => (float) ($material->pivot->percentage ?? 0))->first();
    }
}
