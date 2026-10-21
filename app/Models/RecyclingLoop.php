<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Boucle de recyclage marque ↔ fournisseur.
 * requested → proposed → accepted → in_transit → received → transformed → delivered → closed
 * (declined possible à la demande ou sur la proposition).
 */
class RecyclingLoop extends Model
{
    public const MODES = [
        'restitution' => 'Restitution à la marque',
        'sale' => 'Revente à la marque',
    ];

    public const STATUSES = [
        'requested' => 'Demande envoyée',
        'proposed' => 'Proposition reçue',
        'accepted' => 'Proposition acceptée',
        'in_transit' => 'En route vers le fournisseur',
        'received' => 'Reçu par le fournisseur',
        'transformed' => 'Matière recyclée produite',
        'delivered' => 'Matière recyclée livrée',
        'closed' => 'Boucle fermée',
        'declined' => 'Refusée',
    ];

    /** Les 4 temps de la boucle, tels qu'ils sont montrés aux utilisateurs. */
    public const PHASES = [
        'agreement' => ['label' => 'Accord', 'statuses' => ['requested', 'proposed', 'accepted']],
        'collect' => ['label' => 'Envoi', 'statuses' => ['in_transit', 'received']],
        'transform' => ['label' => 'Recyclage', 'statuses' => ['transformed']],
        'return' => ['label' => 'Retour à la marque', 'statuses' => ['delivered', 'closed']],
    ];

    protected $fillable = [
        'reference', 'brand_id', 'supplier_id', 'requested_by', 'status', 'brand_message', 'wished_pickup_at',
        'output_material_id', 'expected_output_kg', 'mode', 'unit_price', 'ready_by', 'supplier_message', 'decline_reason',
        'received_kg', 'output_kg', 'proof_path', 'output_batch_id',
        'proposed_at', 'accepted_at', 'shipped_at', 'received_at', 'transformed_at', 'delivered_at', 'closed_at', 'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'wished_pickup_at' => 'date', 'ready_by' => 'date',
            'expected_output_kg' => 'decimal:2', 'unit_price' => 'decimal:2', 'received_kg' => 'decimal:2', 'output_kg' => 'decimal:2',
            'proposed_at' => 'datetime', 'accepted_at' => 'datetime', 'shipped_at' => 'datetime', 'received_at' => 'datetime',
            'transformed_at' => 'datetime', 'delivered_at' => 'datetime', 'closed_at' => 'datetime', 'declined_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecyclingLoopItem::class);
    }

    public function outputMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'output_material_id');
    }

    public function outputBatch(): BelongsTo
    {
        return $this->belongsTo(SupplierBatch::class, 'output_batch_id');
    }

    public function totalKg(): float
    {
        return (float) $this->items->sum('quantity_kg');
    }

    public function totalPieces(): int
    {
        return (int) $this->items->sum('pieces');
    }

    /** Rendement réel (matière recyclée / matière envoyée), sinon rendement prévu. */
    public function yieldRate(): ?int
    {
        $input = (float) ($this->received_kg ?: $this->totalKg());
        $output = (float) ($this->output_kg ?: $this->expected_output_kg);

        return $input > 0 && $output > 0 ? (int) round($output / $input * 100) : null;
    }

    public function amount(): ?float
    {
        $kg = (float) ($this->output_kg ?: $this->expected_output_kg);

        return $this->unit_price !== null && $kg > 0 ? round($kg * (float) $this->unit_price, 2) : null;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function modeLabel(): ?string
    {
        return $this->mode ? self::MODES[$this->mode] : null;
    }

    public function phaseIndex(): int
    {
        foreach (array_values(self::PHASES) as $index => $phase) {
            if (in_array($this->status, $phase['statuses'], true)) {
                return $this->status === 'closed' ? 4 : $index;
            }
        }

        return 0;
    }

    /** Qui doit agir : brand, supplier ou null. */
    public function nextActor(): ?string
    {
        return [
            'requested' => 'supplier', 'proposed' => 'brand', 'accepted' => 'brand', 'in_transit' => 'supplier',
            'received' => 'supplier', 'transformed' => 'supplier', 'delivered' => 'brand',
        ][$this->status] ?? null;
    }

    public function nextAction(): ?string
    {
        return [
            'requested' => 'Faire une proposition', 'proposed' => 'Répondre à la proposition', 'accepted' => 'Envoyer les articles',
            'in_transit' => 'Confirmer la réception', 'received' => 'Déclarer la matière produite', 'transformed' => 'Livrer la matière recyclée',
            'delivered' => 'Confirmer la réception',
        ][$this->status] ?? null;
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['closed', 'declined'], true);
    }
}
