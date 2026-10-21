<?php

namespace App\Services;

use App\Models\MaterialOriginCertificate;
use App\Models\PurchaseOrder;
use App\Models\RecyclingLoop;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Score de risque fournisseur (0 = aucun risque, 100 = risque maximal), en 4 facteurs pondérés et expliqués :
 * certificats (35 %), fiabilité des livraisons OTIF (30 %), conformité (20 %), commandes en retard (15 %).
 */
class SupplierRiskService
{
    public const WEIGHTS = ['certificates' => 0.35, 'delivery' => 0.30, 'compliance' => 0.20, 'orders' => 0.15];

    public const LABELS = ['certificates' => 'Certificats', 'delivery' => 'Livraisons (OTIF)', 'compliance' => 'Conformité', 'orders' => 'Commandes en retard'];

    public function __construct(private SupplierScorecardService $scorecard) {}

    /** @return Collection<int, array> du plus risqué au moins risqué */
    public function portfolio(Collection $suppliers): Collection
    {
        $suppliers->loadMissing(['batches.certificates', 'batches.material', 'purchaseOrders.lines.batches', 'brand']);

        return $suppliers->map(fn (Supplier $supplier) => $this->assess($supplier))->sortByDesc('score')->values();
    }

    /** @return array{supplier: Supplier, score: int, level: string, tone: string, factors: array, expiring: array, actions: array} */
    public function assess(Supplier $supplier): array
    {
        $supplier->loadMissing(['batches.certificates', 'batches.material', 'purchaseOrders.lines.batches']);
        $factors = [
            'certificates' => $this->certificates($supplier),
            'delivery' => $this->delivery($supplier),
            'compliance' => $this->compliance($supplier),
            'orders' => $this->lateOrders($supplier),
        ];
        $score = 0.0;
        foreach ($factors as $key => $factor) {
            $score += $factor['score'] * self::WEIGHTS[$key];
        }
        $score = (int) round($score);
        if ($supplier->status === 'suspended') {
            $score = max($score, 80);
        }
        [$level, $tone] = $score >= 60 ? ['élevé', 'rose'] : ($score >= 30 ? ['moyen', 'amber'] : ['faible', 'emerald']);

        return [
            'supplier' => $supplier,
            'score' => $score,
            'level' => $level,
            'tone' => $tone,
            'factors' => collect($factors)->map(fn ($factor, $key) => $factor + ['label' => self::LABELS[$key], 'weight' => (int) (self::WEIGHTS[$key] * 100)])->all(),
            'expiring' => $this->expiring($supplier),
            'actions' => $this->actions($factors),
        ];
    }

    /** Certificats qui expirent dans les $days jours (ou déjà expirés), tous fournisseurs confondus. */
    public function expiringCertificates(Collection $suppliers, int $days = 120): Collection
    {
        return $suppliers->flatMap(fn (Supplier $supplier) => $this->expiring($supplier, $days))->sortBy('days')->values();
    }

    private function certificates(Supplier $supplier): array
    {
        $issues = [];
        $worst = 0;
        // Les lots de matière recyclée issus d'une boucle de recyclage ont pour preuve la boucle elle-même.
        $recycled = RecyclingLoop::where('supplier_id', $supplier->id)->whereNotNull('output_batch_id')->pluck('output_batch_id')->all();
        foreach ($supplier->batches->whereNotIn('id', $recycled) as $batch) {
            $valid = $batch->certificates->reject(fn (MaterialOriginCertificate $c) => $c->status === 'rejected' || $c->isNotACertificate());
            if ($valid->isEmpty()) {
                $worst = max($worst, 60);
                $issues[] = 'Lot '.$batch->batch_code.' sans certificat valable';
            }
            foreach ($batch->certificates as $certificate) {
                $days = $certificate->expires_at ? (int) now()->startOfDay()->diffInDays($certificate->expires_at, false) : null;
                [$risk, $text] = match (true) {
                    $certificate->status === 'rejected' => [90, $certificate->displayName().' refusé'],
                    $days !== null && $days < 0 => [100, $certificate->displayName().' expiré depuis '.abs($days).' j'],
                    $days !== null && $days <= 30 => [70, $certificate->displayName().' expire dans '.$days.' j'],
                    $days !== null && $days <= 90 => [35, $certificate->displayName().' expire dans '.$days.' j'],
                    $certificate->status === 'pending' => [25, $certificate->displayName().' en attente de vérification'],
                    default => [0, null],
                };
                $worst = max($worst, $risk);
                if ($text) {
                    $issues[] = $text;
                }
            }
        }
        if ($supplier->batches->isEmpty()) {
            $worst = 50;
            $issues[] = 'Aucun lot déclaré : provenance non documentée';
        }

        return ['score' => $worst, 'detail' => $issues === [] ? 'Tous les certificats sont valables.' : implode(' · ', array_slice($issues, 0, 3)).(count($issues) > 3 ? ' · +'.(count($issues) - 3) : ''), 'issues' => $issues];
    }

    private function delivery(Supplier $supplier): array
    {
        $card = $this->scorecard->card($supplier);
        if (($card['evaluated'] ?? 0) === 0 || $card['otif'] === null) {
            return ['score' => 40, 'detail' => 'Pas encore de livraison évaluée.', 'otif' => null];
        }

        return [
            'score' => (int) round(100 - (float) $card['otif']),
            'detail' => 'OTIF '.round((float) $card['otif']).' % sur '.$card['evaluated'].' commande(s)'.($card['avg_delay_days'] ? ', retard moyen '.$card['avg_delay_days'].' j' : ''),
            'otif' => $card['otif'],
        ];
    }

    private function compliance(Supplier $supplier): array
    {
        if ($supplier->compliance_score === null) {
            return ['score' => 50, 'detail' => 'Score de conformité non renseigné.'];
        }

        return ['score' => 100 - (int) $supplier->compliance_score, 'detail' => 'Conformité déclarée '.$supplier->compliance_score.' %.'];
    }

    private function lateOrders(Supplier $supplier): array
    {
        $late = $supplier->purchaseOrders->filter(fn (PurchaseOrder $order) => $order->isLate());

        return [
            'score' => min(100, $late->count() * 35),
            'detail' => $late->isEmpty() ? 'Aucune commande en retard.' : $late->count().' commande(s) en retard : '.$late->pluck('reference')->join(', '),
        ];
    }

    private function expiring(Supplier $supplier, int $days = 120): array
    {
        $rows = [];
        foreach ($supplier->batches as $batch) {
            foreach ($batch->certificates as $certificate) {
                if (! $certificate->expires_at || $certificate->status === 'rejected') {
                    continue;
                }
                $left = (int) now()->startOfDay()->diffInDays($certificate->expires_at, false);
                if ($left <= $days) {
                    $rows[] = ['certificate' => $certificate, 'supplier' => $supplier, 'batch' => $batch, 'days' => $left, 'date' => $certificate->expires_at];
                }
            }
        }

        return $rows;
    }

    private function actions(array $factors): array
    {
        $actions = [];
        if ($factors['certificates']['score'] >= 70) {
            $actions[] = 'Demander dès maintenant le renouvellement des certificats expirés ou qui expirent dans le mois.';
        } elseif ($factors['certificates']['score'] >= 50) {
            $actions[] = 'Exiger un certificat de provenance pour chaque lot avant de l’associer à un produit.';
        }
        if ($factors['delivery']['score'] >= 40 && ($factors['delivery']['otif'] ?? null) !== null) {
            $actions[] = 'Fixer un plan d’amélioration des délais (OTIF cible ≥ 90 %) ou diversifier l’approvisionnement.';
        }
        if ($factors['orders']['score'] > 0) {
            $actions[] = 'Relancer les commandes en retard et confirmer une nouvelle date de livraison.';
        }
        if ($factors['compliance']['score'] >= 40) {
            $actions[] = 'Planifier un audit de conformité sociale et environnementale.';
        }

        return $actions ?: ['Fournisseur fiable : maintenir le suivi à chaque commande.'];
    }
}
