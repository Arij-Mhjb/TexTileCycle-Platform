<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\MediaStorageService;

class TradeInRequest extends Model
{
    use HasFactory;

    public const CONDITIONS = ['good' => 'Très bon', 'wearable' => 'Portable', 'minor_damage' => 'Petits défauts', 'damaged' => 'Endommagé'];

    protected $fillable = [
        'reference', 'user_id', 'product_id', 'product_name', 'condition', 'description', 'proposed_points', 'status',
        'evaluated_by', 'evaluation_note', 'evaluated_at', 'completed_at', 'collection_point_id', 'qr_token',
        'deposit_evidence', 'deposited_at', 'received_at',
    ];

    protected function casts(): array
    {
        return [
            'evaluated_at' => 'datetime', 'completed_at' => 'datetime', 'deposited_at' => 'datetime',
            'received_at' => 'datetime', 'deposit_evidence' => 'array',
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function collectionPoint(): BelongsTo
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    public function itemName(): string
    {
        return $this->product?->name ?? $this->product_name ?? 'Article';
    }

    public function statusLabel(): string
    {
        return [
            'submitted' => 'Envoyée', 'evaluated' => 'Offre reçue', 'accepted' => 'Acceptée — à déposer',
            'deposited' => 'Dépôt déclaré', 'declined' => 'Refusée', 'completed' => 'Terminée',
        ][$this->status] ?? $this->status;
    }

    public function evidenceUrl(string $actor): ?string
    {
        $file = data_get($this->deposit_evidence, $actor);
        if (! is_array($file)) return null;
        return app(MediaStorageService::class)->url(
            $file['path'] ?? null, $file['storage_disk'] ?? null, $file['cloudinary_public_id'] ?? null,
            $file['secure_url'] ?? null, $file['delivery_type'] ?? null, $file['format'] ?? null, $file['version'] ?? null,
        );
    }
}
