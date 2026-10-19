<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\MaterialOriginCertificate;
use App\Models\ProductSupplierLink;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\Ai\SupplierAiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Traçabilité fournisseurs : contrôle de conformité lot ↔ produit,
 * revue admin des lots et certificats, score de conformité fournisseur.
 */
class SupplierTraceabilityService
{
    public function __construct(private SupplierAiService $ai) {}

    /**
     * Ajoute un certificat à partir de n'importe quelle image : l'IA décrit toujours ce qu'elle voit.
     * - Certificat : les informations sont lues et le certificat attend la vérification de l'admin.
     * - Autre chose (photo, schéma…) : l'image est conservée avec la description de l'IA, marquée
     *   « pas un certificat » ; elle ne compte pas comme preuve, et l'admin peut corriger l'IA.
     *
     * @return array{ok: bool, recognized: bool, message: string, description: ?string, certificate?: MaterialOriginCertificate}
     */
    public function addCertificateFromPhoto(SupplierBatch $batch, UploadedFile $file): array
    {
        $result = $this->ai->extractCertificate($file->getRealPath(), $batch);
        $description = $result['description'] ?? null;

        if (($result['is_certificate'] ?? true) === false) {
            $certificate = $batch->certificates()->create([
                'document_path' => $file->store('certificates/'.$batch->id, 'local'),
                'ai_extraction' => $result,
                'status' => 'rejected',
                'review_note' => 'Rejeté automatiquement par l’IA : ce n’est pas un certificat. '.$description,
                'reviewed_at' => now(),
            ]);
            $this->refreshComplianceScore($batch->supplier);

            return [
                'ok' => true, 'recognized' => false, 'certificate' => $certificate, 'description' => $description,
                'message' => 'Image enregistrée, mais ce n’est pas un certificat. L’IA y voit : '.$description.' Elle ne compte pas comme preuve : ajoutez la photo du certificat (GRS, GOTS, RWS, OEKO-TEX…).',
            ];
        }

        $fields = $result['fields'];
        $certificate = $batch->certificates()->create([
            'type' => array_key_exists((string) ($fields['type'] ?? ''), MaterialOriginCertificate::TYPES) ? $fields['type'] : null,
            'certificate_number' => $this->cleanText($fields['certificate_number'] ?? null),
            'issuer' => $this->cleanText($fields['issuer'] ?? null),
            'issued_at' => $this->cleanDate($fields['issued_at'] ?? null),
            'expires_at' => $this->cleanDate($fields['expires_at'] ?? null),
            'document_path' => $file->store('certificates/'.$batch->id, 'local'),
            'ai_extraction' => $result,
            'status' => 'pending',
        ]);
        $this->refreshComplianceScore($batch->supplier);

        $read = collect(['type', 'certificate_number', 'issuer', 'issued_at', 'expires_at'])->filter(fn ($key) => filled($fields[$key] ?? null))->count();

        return [
            'ok' => true,
            'recognized' => true,
            'certificate' => $certificate,
            'description' => $description,
            'message' => $read > 0
                ? 'Certificat ajouté : l’IA a lu '.$read.' information(s) sur 5. TexTileCycle va le vérifier.'
                : 'Certificat ajouté. L’IA n’a pas pu le lire : TexTileCycle le vérifiera directement sur le document.',
        ];
    }

    /** Code de lot généré automatiquement : LOT-<3 lettres du fournisseur>-<AAMM>-<n°>, ex. LOT-FIL-2610-003. */
    public function nextBatchCode(Supplier $supplier): string
    {
        $prefix = 'LOT-'.Str::upper(Str::padRight(Str::substr(Str::slug($supplier->name, ''), 0, 3), 3, 'X')).'-'.now()->format('ym').'-';
        $number = SupplierBatch::where('batch_code', 'like', $prefix.'%')->count() + 1;
        do {
            $code = $prefix.str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
        } while (SupplierBatch::where('batch_code', $code)->exists());

        return $code;
    }

    private function cleanText(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, 150) : null;
    }

    private function cleanDate(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

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
            $certificate->displayName().' — lot '.$certificate->batch->batch_code.'.');

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

        // Les images que l'IA a reconnues comme « pas un certificat » ne pèsent pas dans le score (sauf si l'admin les a validées).
        $certificates = $batches->flatMap->certificates->reject(fn (MaterialOriginCertificate $certificate) => $certificate->isNotACertificate());
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
