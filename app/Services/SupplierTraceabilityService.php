<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\MaterialOriginCertificate;
use App\Models\ProductSupplierLink;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Traçabilité fournisseurs : contrôle de conformité lot ↔ produit,
 * revue admin des lots et certificats, score de conformité fournisseur.
 */
class SupplierTraceabilityService
{
    /**
     * Compare le lot fournisseur avec la composition déclarée du produit.
     *
     * @return array<int, string> Liste des écarts détectés (vide = conforme).
     */
    public function checkConformity(ProductSupplierLink $link): array
    {
        $link->loadMissing(['batch.certificates', 'batch.material', 'product.materials']);
        $batch = $link->batch;
        $issues = [];

        if ($batch->status !== 'verified') {
            $issues[] = 'Le lot '.$batch->batch_code.' n’a pas encore été vérifié par TexTileCycle.';
        }

        $declared = $batch->material_id ? $link->product->materials->firstWhere('id', $batch->material_id) : null;

        if (! $batch->material_id) {
            $issues[] = 'La matière du lot n’est pas renseignée.';
        } elseif (! $declared) {
            $issues[] = 'La matière « '.$batch->material->name.' » n’apparaît pas dans la composition déclarée du produit.';
        } else {
            $declaredOrigin = $declared->pivot->origin_country;
            if ($declaredOrigin && Str::lower(trim($declaredOrigin)) !== Str::lower(trim($batch->origin_country))) {
                $issues[] = 'Origine déclarée « '.$declaredOrigin.' » différente de l’origine du lot « '.$batch->origin_country.' ».';
            }
            if ($declared->pivot->certified && ! $batch->hasValidCertificate()) {
                $issues[] = 'Le produit déclare une matière certifiée mais le lot n’a aucun certificat valide.';
            }
        }

        if (! $batch->hasValidCertificate()) {
            $issues[] = 'Aucun certificat de provenance vérifié et en cours de validité.';
        }

        $link->update([
            'conformity_status' => $issues === [] ? 'conform' : 'mismatch',
            'conformity_note' => $issues === [] ? 'Lot, matière, origine et certificat concordent avec la déclaration produit.' : implode("\n", $issues),
            'checked_at' => now(),
        ]);

        return $issues;
    }

    public function reviewBatch(SupplierBatch $batch, User $admin, string $status, ?string $note = null): SupplierBatch
    {
        $batch->update([
            'status' => $status,
            'verification_note' => $note,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $this->refreshComplianceScore($batch->supplier);
        $this->notifyBrand($batch->supplier, 'supplier_batch_'.$status,
            $status === 'verified' ? 'Lot fournisseur vérifié' : 'Lot fournisseur refusé',
            'Lot '.$batch->batch_code.' ('.$batch->supplier->name.') : '.($note ?: ($status === 'verified' ? 'traçabilité confirmée.' : 'informations insuffisantes.')));

        return $batch;
    }

    public function reviewCertificate(MaterialOriginCertificate $certificate, User $admin, string $status, ?string $note = null): MaterialOriginCertificate
    {
        $certificate->update([
            'status' => $status,
            'review_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $supplier = $certificate->batch->supplier;
        $this->refreshComplianceScore($supplier);
        $this->notifyBrand($supplier, 'supplier_certificate_'.$status,
            $status === 'verified' ? 'Certificat de provenance validé' : 'Certificat de provenance refusé',
            $certificate->type.' n° '.$certificate->certificate_number.' — lot '.$certificate->batch->batch_code.'.');

        return $certificate;
    }

    /**
     * Score de conformité : 50 % lots vérifiés + 50 % certificats valides.
     */
    public function refreshComplianceScore(Supplier $supplier): ?int
    {
        $batches = $supplier->batches()->with('certificates')->get();
        if ($batches->isEmpty()) {
            $supplier->update(['compliance_score' => null]);

            return null;
        }

        $certificates = $batches->flatMap->certificates;
        $batchRatio = $batches->where('status', 'verified')->count() / $batches->count();
        $certificateRatio = $certificates->isEmpty() ? 0 : $certificates->filter->isValid()->count() / $certificates->count();
        $score = (int) round($batchRatio * 50 + $certificateRatio * 50);

        $supplier->update(['compliance_score' => $score]);

        return $score;
    }

    public function nextPurchaseOrderReference(): string
    {
        return 'PO-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }

    private function notifyBrand(Supplier $supplier, string $type, string $title, string $message): void
    {
        $userId = $supplier->brand?->user_id;
        if (! $userId) {
            return;
        }

        BrandNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => route('brand.suppliers.show', $supplier),
        ]);
    }
}
