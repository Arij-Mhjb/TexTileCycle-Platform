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
    /** Ce que la marque veut en retour (choisi dans la demande). Le prix est toujours calculé automatiquement. */
    public const MODES = [
        'recover' => 'Récupérer la matière recyclée',
        'buyback' => 'Le fournisseur la rachète',
    ];

    public const MODE_HINTS = [
        'recover' => 'Vous payez les frais de recyclage et recevez la matière, moins chère que du neuf.',
        'buyback' => 'Le fournisseur garde la matière et vous verse un avoir, frais déjà déduits.',
    ];

    public const STATUSES = [
        'requested' => 'Demande envoyée',
        'proposed' => 'Proposition reçue',
        'accepted' => 'Proposition acceptée',
        'in_transit' => 'En route vers le fournisseur',
        'received' => 'Reçu par le fournisseur',
        'transformed' => 'Matière recyclée produite',
        'delivered' => 'Matière recyclée livrée',
        'closed' => 'Terminée',
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
        'mode', 'output_material_id', 'expected_output_kg', 'pricing', 'amount', 'ready_by', 'supplier_message', 'decline_reason',
        'received_kg', 'output_kg', 'proof_path', 'output_batch_id', 'invoice_number', 'invoiced_at', 'recycling_schedule_id',
        'proposed_at', 'accepted_at', 'shipped_at', 'received_at', 'transformed_at', 'delivered_at', 'closed_at', 'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'wished_pickup_at' => 'date', 'ready_by' => 'date',
            'expected_output_kg' => 'decimal:2', 'pricing' => 'array', 'amount' => 'decimal:2', 'received_kg' => 'decimal:2', 'output_kg' => 'decimal:2',
            'proposed_at' => 'datetime', 'accepted_at' => 'datetime', 'shipped_at' => 'datetime', 'received_at' => 'datetime',
            'transformed_at' => 'datetime', 'delivered_at' => 'datetime', 'closed_at' => 'datetime', 'declined_at' => 'datetime', 'invoiced_at' => 'datetime',
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
        return $this->hasMany(RecyclingLoopItem::class)->orderBy('id');
    }

    public function outputMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'output_material_id');
    }

    public function outputBatch(): BelongsTo
    {
        return $this->belongsTo(SupplierBatch::class, 'output_batch_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RecyclingSchedule::class, 'recycling_schedule_id');
    }

    /** Facture (récupération) ou avoir (rachat) disponible une fois le prix définitif. */
    public function hasInvoice(): bool
    {
        return in_array($this->status, ['transformed', 'delivered', 'closed'], true) && ($this->pricing['stage'] ?? null) === 'final';
    }

    public function invoiceLabel(): string
    {
        return $this->isBuyback() ? 'Avoir' : 'Facture';
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

    /** Montant calculé : positif = la marque paie les frais, négatif = la marque reçoit un avoir. */
    public function amount(): ?float
    {
        return $this->amount !== null ? (float) $this->amount : null;
    }

    public function isBuyback(): bool
    {
        return $this->mode === 'buyback';
    }

    /** Le prix affiché est-il définitif (matière pesée et produite) ? */
    public function priceIsFinal(): bool
    {
        return ($this->pricing['stage'] ?? null) === 'final';
    }

    public function statusLabel(): string
    {
        if ($this->isBuyback() && $this->status === 'delivered') {
            return 'Avoir versé';
        }

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

    /** Le bouton unique à montrer à celui qui doit agir, en mots simples. */
    public function nextAction(): ?string
    {
        return [
            'requested' => 'Confirmer ce que je peux produire',
            'proposed' => 'Accepter la proposition',
            'accepted' => 'Confirmer l’envoi du colis',
            'in_transit' => 'Peser et confirmer la réception',
            'received' => 'Déclarer la matière produite',
            'transformed' => $this->isBuyback() ? 'Verser l’avoir' : 'Livrer la matière',
            'delivered' => $this->isBuyback() ? 'Confirmer l’avoir reçu' : 'Confirmer la réception',
        ][$this->status] ?? null;
    }

    /** Une phrase qui dit où on en est, du point de vue de celui qui regarde (brand, supplier, admin). */
    public function situation(string $space): string
    {
        $brand = $this->brand?->name ?? 'La marque';
        $supplier = $this->supplier?->name ?? 'Le fournisseur';
        $output = $this->outputMaterial?->name ?? 'matière recyclée';
        $kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';
        $mine = fn (string $actor) => $space === $actor;

        return match ($this->status) {
            'requested' => $mine('supplier')
                ? $brand.' veut vous confier '.$kg($this->totalKg()).'. Vérifiez et confirmez ce que vous pouvez produire : le prix se calcule tout seul.'
                : 'Demande envoyée à '.$supplier.'. Il vérifie ce qu’il peut produire.',
            'proposed' => $mine('brand')
                ? $supplier.' peut produire '.$kg($this->expected_output_kg).' de '.mb_strtolower($output).'. Acceptez pour lancer le recyclage.'
                : 'Proposition envoyée. On attend la réponse de '.$brand.'.',
            'accepted' => $mine('brand')
                ? 'C’est d’accord ! Préparez le colis pour '.$supplier.' puis confirmez l’envoi.'
                : $brand.' a accepté. Le colis va bientôt partir.',
            'in_transit' => $mine('supplier')
                ? 'Le colis de '.$brand.' arrive. Pesez-le à l’arrivée et confirmez.'
                : 'Le colis est en route vers '.$supplier.'.',
            'received' => $mine('supplier')
                ? 'Colis reçu ('.$kg($this->received_kg).'). Recyclez-le puis déclarez la matière obtenue.'
                : $supplier.' a reçu '.$kg($this->received_kg).'. Le recyclage est en cours.',
            'transformed' => $mine('supplier')
                ? ($this->isBuyback() ? 'Matière produite. Versez maintenant l’avoir à '.$brand.'.' : 'Matière produite. Livrez-la maintenant à '.$brand.'.')
                : $kg($this->output_kg).' de '.mb_strtolower($output).' sont prêts chez '.$supplier.'.',
            'delivered' => $mine('brand')
                ? ($this->isBuyback() ? $supplier.' a versé votre avoir. Confirmez pour clôturer.' : 'La matière recyclée est livrée. Confirmez que vous l’avez reçue.')
                : 'Livré. On attend la confirmation de '.$brand.'.',
            'closed' => 'Terminé : '.$kg($this->received_kg ?: $this->totalKg()).' de rebuts sont redevenus '.$kg($this->output_kg).' de '.mb_strtolower($output).'.',
            'declined' => 'Refusée'.($this->decline_reason ? ' : « '.$this->decline_reason.' »' : '.'),
            default => $this->statusLabel(),
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['closed', 'declined'], true);
    }
}
