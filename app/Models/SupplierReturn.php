<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Retour de matière marque → fournisseur et sa seconde vie.
 * requested → accepted → in_transit → received → completed → verified (ou correction demandée → completed).
 */
class SupplierReturn extends Model
{
    public const REASONS = [
        'surplus' => 'Surplus (matière en trop)',
        'defect' => 'Défaut qualité',
        'wrong_item' => 'Erreur de livraison',
    ];

    public const DESTINATIONS = [
        'resale' => 'Revente à une autre marque',
        'recycling' => 'Recyclage en nouvelle fibre',
        'upcycling' => 'Surcyclage (accessoires, chutes valorisées)',
    ];

    public const STATUSES = [
        'requested' => 'En attente du fournisseur',
        'accepted' => 'Reprise acceptée',
        'in_transit' => 'Renvoyé par la marque',
        'received' => 'Reçu par le fournisseur',
        'completed' => 'Seconde vie déclarée',
        'correction' => 'Correction demandée',
        'verified' => 'Vérifié par TexTileCycle',
    ];

    /** Étapes de la frise (statut => libellé court). */
    public const STEPS = [
        'requested' => 'Demande', 'accepted' => 'Reprise', 'in_transit' => 'Renvoi', 'received' => 'Réception', 'completed' => 'Seconde vie', 'verified' => 'Vérifié',
    ];

    protected $fillable = [
        'reference', 'brand_id', 'supplier_id', 'supplier_batch_id', 'purchase_order_id', 'material_id', 'requested_by',
        'quantity_kg', 'reason', 'description', 'photo_path', 'ai_analysis', 'status', 'destination', 'pickup_at', 'supplier_note',
        'resold_kg', 'recycled_kg', 'upcycled_kg', 'waste_kg', 'proof_path', 'admin_note', 'verified_by',
        'accepted_at', 'shipped_at', 'received_at', 'completed_at', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2', 'resold_kg' => 'decimal:2', 'recycled_kg' => 'decimal:2', 'upcycled_kg' => 'decimal:2', 'waste_kg' => 'decimal:2',
            'ai_analysis' => 'array', 'pickup_at' => 'date',
            'accepted_at' => 'datetime', 'shipped_at' => 'datetime', 'received_at' => 'datetime', 'completed_at' => 'datetime', 'verified_at' => 'datetime',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SupplierBatch::class, 'supplier_batch_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function destinationLabel(): ?string
    {
        return $this->destination ? (self::DESTINATIONS[$this->destination] ?? $this->destination) : null;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusTone(): string
    {
        return ['verified' => 'good', 'completed' => 'suppliers', 'correction' => 'bad', 'requested' => 'warn'][$this->status] ?? 'neutral';
    }

    /** Kg qui ont réellement échappé au déchet (revendus + recyclés + surcyclés). */
    public function savedKg(): float
    {
        return (float) $this->resold_kg + (float) $this->recycled_kg + (float) $this->upcycled_kg;
    }

    public function stepIndex(): int
    {
        $status = $this->status === 'correction' ? 'completed' : $this->status;

        return (int) array_search($status, array_keys(self::STEPS), true);
    }
}
