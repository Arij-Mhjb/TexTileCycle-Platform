<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CertificateAlert;
use App\Models\MaterialOriginCertificate;

/**
 * Alertes avant l'expiration des certificats de provenance : à 30 jours, à 7 jours, puis le jour de l'expiration.
 * La marque et le fournisseur sont prévenus une seule fois par seuil.
 */
class CertificateAlertService
{
    public const THRESHOLDS = [30, 7, 0];

    public function __construct(private NotificationCenter $notifications) {}

    /** @return int nombre d'alertes envoyées */
    public function run(?Brand $brand = null): int
    {
        $sent = 0;
        $certificates = MaterialOriginCertificate::with(['batch.supplier.brand', 'batch.material'])
            ->whereNotNull('expires_at')->where('status', '!=', 'rejected')
            ->whereDate('expires_at', '<=', now()->addDays(self::THRESHOLDS[0]))
            ->when($brand, fn ($query) => $query->whereHas('batch.supplier', fn ($supplier) => $supplier->where('brand_id', $brand->id)))
            ->get();

        foreach ($certificates as $certificate) {
            $supplier = $certificate->batch?->supplier;
            if (! $supplier) {
                continue;
            }
            $days = (int) now()->startOfDay()->diffInDays($certificate->expires_at, false);
            $threshold = $days <= 0 ? 0 : ($days <= 7 ? 7 : 30);
            if (CertificateAlert::where('material_origin_certificate_id', $certificate->id)->where('threshold', '<=', $threshold)->exists()) {
                continue;
            }
            CertificateAlert::create(['material_origin_certificate_id' => $certificate->id, 'threshold' => $threshold, 'sent_at' => now()]);

            $what = $certificate->displayName().' (lot '.$certificate->batch->batch_code.', '.$certificate->batch->material?->name.')';
            $when = $days < 0 ? 'a expiré le '.$certificate->expires_at->format('d/m/Y') : ($days === 0 ? 'expire aujourd’hui' : 'expire dans '.$days.' jour(s), le '.$certificate->expires_at->format('d/m/Y'));
            $type = $days <= 0 ? 'certificate_expired' : 'certificate_expiring';
            $this->notifications->send($supplier->brand?->user_id, $type, 'Certificat de '.$supplier->name.' : '.($days <= 0 ? 'expiré' : 'expiration proche'),
                $what.' '.$when.'. Demandez le renouvellement.', route('brand.suppliers-risk'));
            $this->notifications->send($supplier->user_id, $type, 'Votre certificat '.($days <= 0 ? 'a expiré' : 'expire bientôt'),
                $what.' '.$when.'. Ajoutez le certificat renouvelé.', route('supplier.batches.index'));
            $sent++;
        }

        return $sent;
    }
}
