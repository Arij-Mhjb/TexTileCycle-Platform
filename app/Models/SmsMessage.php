<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = ['supplier_id', 'to', 'body', 'driver', 'status', 'provider_id', 'error'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function statusLabel(): string
    {
        return ['sent' => 'Envoyé', 'logged' => 'Enregistré (mode démo)', 'failed' => 'Échec'][$this->status] ?? $this->status;
    }
}
