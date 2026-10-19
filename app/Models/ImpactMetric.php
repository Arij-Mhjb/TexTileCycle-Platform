<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpactMetric extends Model
{
    use HasFactory;

    /** Bases de données ACV reconnues pour justifier un facteur d'impact (ou « Autre » saisi à la main). */
    public const SOURCES = [
        'Base Empreinte (ADEME)',
        'Higg Materials Sustainability Index',
        'Ecoinvent',
        'Textile Exchange — Materials Benchmark',
        'Fiche fournisseur / déclaration environnementale',
        'Valeur indicative (démo)',
    ];

    protected $fillable = ['material_id', 'impact_category_id', 'value_per_kg', 'source'];

    protected function casts(): array
    {
        return ['value_per_kg' => 'decimal:4'];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ImpactCategory::class, 'impact_category_id');
    }
}
