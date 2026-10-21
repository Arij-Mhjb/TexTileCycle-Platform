<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alerte d'expiration déjà envoyée pour un certificat (seuils : 30 jours, 7 jours, expiré). */
class CertificateAlert extends Model
{
    public $timestamps = false;

    protected $fillable = ['material_origin_certificate_id', 'threshold', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(MaterialOriginCertificate::class, 'material_origin_certificate_id');
    }
}
