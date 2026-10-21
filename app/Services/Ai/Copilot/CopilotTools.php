<?php

namespace App\Services\Ai\Copilot;

use App\Models\Brand;
use App\Models\CarbonTarget;
use App\Models\PurchaseOrder;
use App\Models\RecyclingLoop;
use App\Models\Supplier;
use App\Services\Ai\GreenClaimsService;
use App\Services\CarbonTargetService;
use App\Services\SupplierRiskService;
use App\Services\SupplierScorecardService;
use Illuminate\Support\Str;

/**
 * Outils du copilote : chaque outil lit les vraies données de la marque (jamais celles d'une autre marque)
 * et renvoie un résultat compact, que le LLM cite dans sa réponse — ou que le mode local résume lui-même.
 */
class CopilotTools
{
    public const LABELS = [
        'fournisseurs_risque' => 'Risque des fournisseurs',
        'certificats_expirants' => 'Certificats qui expirent',
        'performance_fournisseur' => 'Fiche d’un fournisseur',
        'commandes_en_cours' => 'Commandes en cours',
        'impact_produits' => 'Impact des produits',
        'objectifs_carbone' => 'Objectifs carbone',
        'recyclage_bilan' => 'Bilan du recyclage',
        'verifier_allegation' => 'Contrôle anti-greenwashing',
    ];

    public function __construct(
        private SupplierRiskService $risk,
        private SupplierScorecardService $scorecard,
        private CarbonTargetService $carbon,
        private GreenClaimsService $claims,
    ) {}

    /** Définitions au format « function calling » (OpenAI / Hugging Face). */
    public function definitions(): array
    {
        $tool = fn (string $name, string $description, array $properties = [], array $required = []) => ['type' => 'function', 'function' => [
            'name' => $name, 'description' => $description,
            'parameters' => ['type' => 'object', 'properties' => (object) $properties, 'required' => $required],
        ]];

        return [
            $tool('fournisseurs_risque', 'Liste les fournisseurs de la marque avec leur score de risque (0 = sûr, 100 = très risqué), le niveau et les raisons (certificats, OTIF, conformité, retards).'),
            $tool('certificats_expirants', 'Certificats de provenance (GOTS, GRS, RWS…) expirés ou qui expirent bientôt.', ['jours' => ['type' => 'integer', 'description' => 'Horizon en jours (défaut 60)']]),
            $tool('performance_fournisseur', 'Fiche détaillée d’un fournisseur : OTIF, retards, délai de réponse, score de risque et actions conseillées.', ['nom' => ['type' => 'string', 'description' => 'Nom (ou début du nom) du fournisseur']], ['nom']),
            $tool('commandes_en_cours', 'Bons de commande en cours auprès des fournisseurs, avec retard éventuel et pourcentage expédié.'),
            $tool('impact_produits', 'Empreinte environnementale des produits : CO₂e et eau par unité, score et classe A–E.', ['saison' => ['type' => 'string', 'description' => 'Collection, ex. « AH 2026 » (facultatif)']]),
            $tool('objectifs_carbone', 'Objectifs carbone de la marque : budget, consommation, dépassements et leviers chiffrés (passage au recyclé).'),
            $tool('recyclage_bilan', 'Bilan des demandes de recyclage avec les fournisseurs : kilos collectés et recyclés, frais payés, avoirs reçus, demandes à traiter.'),
            $tool('verifier_allegation', 'Analyse un texte marketing et signale les allégations écologiques vagues ou trompeuses (directive UE 2024/825).', ['texte' => ['type' => 'string', 'description' => 'Texte à analyser']], ['texte']),
        ];
    }

    public function run(string $name, array $args, Brand $brand): array
    {
        return match ($name) {
            'fournisseurs_risque' => $this->suppliersRisk($brand),
            'certificats_expirants' => $this->expiring($brand, (int) ($args['jours'] ?? 60)),
            'performance_fournisseur' => $this->supplier($brand, (string) ($args['nom'] ?? '')),
            'commandes_en_cours' => $this->orders($brand),
            'impact_produits' => $this->impact($brand, $args['saison'] ?? null),
            'objectifs_carbone' => $this->targets($brand),
            'recyclage_bilan' => $this->recycling($brand),
            'verifier_allegation' => $this->claim((string) ($args['texte'] ?? '')),
            default => ['erreur' => 'Outil inconnu : '.$name],
        };
    }

    private function suppliers(Brand $brand)
    {
        return Supplier::where('brand_id', $brand->id)->orderBy('name')->get();
    }

    private function suppliersRisk(Brand $brand): array
    {
        return ['fournisseurs' => $this->risk->portfolio($this->suppliers($brand))->map(fn ($row) => [
            'nom' => $row['supplier']->name, 'pays' => $row['supplier']->country, 'score_risque' => $row['score'], 'niveau' => $row['level'],
            'raisons' => collect($row['factors'])->filter(fn ($factor) => $factor['score'] >= 35)->map(fn ($factor) => $factor['label'].' : '.$factor['detail'])->values()->all(),
            'action' => $row['actions'][0] ?? null,
        ])->all()];
    }

    private function expiring(Brand $brand, int $days): array
    {
        return ['horizon_jours' => $days, 'certificats' => $this->risk->expiringCertificates($this->suppliers($brand)->load('batches.certificates', 'batches.material'), max(1, min(365, $days)))
            ->map(fn ($row) => [
                'certificat' => $row['certificate']->displayName(), 'fournisseur' => $row['supplier']->name, 'lot' => $row['batch']->batch_code,
                'matiere' => $row['batch']->material?->name, 'expire_le' => $row['date']->format('d/m/Y'), 'jours_restants' => $row['days'],
            ])->values()->all()];
    }

    private function supplier(Brand $brand, string $name): array
    {
        $needle = Str::lower(Str::ascii($name));
        $supplier = $this->suppliers($brand)->first(fn ($s) => $needle !== '' && Str::contains(Str::lower(Str::ascii($s->name)), $needle));
        if (! $supplier) {
            return ['erreur' => 'Aucun fournisseur ne correspond à « '.$name.' ».', 'fournisseurs_connus' => $this->suppliers($brand)->pluck('name')->all()];
        }
        $card = $this->scorecard->card($supplier->load('purchaseOrders.lines.batches'));
        $risk = $this->risk->assess($supplier);

        return [
            'nom' => $supplier->name, 'pays' => $supplier->country, 'statut' => $supplier->statusLabel(),
            'otif_pourcent' => $card['otif'], 'commandes_evaluees' => $card['evaluated'], 'retard_moyen_jours' => $card['avg_delay_days'],
            'reponse_moyenne_heures' => $card['avg_reply_hours'], 'note' => $card['grade'] ?? null,
            'score_risque' => $risk['score'], 'niveau_risque' => $risk['level'],
            'facteurs' => collect($risk['factors'])->map(fn ($f) => $f['label'].' ('.$f['score'].'/100) : '.$f['detail'])->values()->all(),
            'actions' => $risk['actions'],
        ];
    }

    private function orders(Brand $brand): array
    {
        $orders = PurchaseOrder::with('supplier', 'lines.batches')->where('brand_id', $brand->id)
            ->whereIn('status', ['sent', 'ordered', 'countered', 'accepted', 'partially_shipped', 'shipped'])->latest('ordered_at')->take(15)->get();

        return ['commandes' => $orders->map(fn (PurchaseOrder $order) => [
            'reference' => $order->reference, 'fournisseur' => $order->supplier?->name, 'statut' => $order->statusLabel(),
            'livraison_prevue' => $order->expected_at?->format('d/m/Y'), 'en_retard' => $order->isLate(),
            'expedie_pourcent' => $order->lines->isNotEmpty() ? $order->shippedPercent() : null,
        ])->all()];
    }

    private function impact(Brand $brand, ?string $season): array
    {
        return ['saison' => $season, 'produits' => $this->carbon->reports($brand, $season ?: null)->map(fn ($report) => [
            'produit' => $report->product?->name, 'saison' => $report->season, 'co2e_kg_par_unite' => round((float) $report->carbon_kg, 2),
            'eau_litres_par_unite' => round((float) $report->water_l), 'score' => $report->impact_score, 'classe' => $report->grade,
        ])->sortByDesc('co2e_kg_par_unite')->values()->all()];
    }

    private function targets(Brand $brand): array
    {
        $targets = CarbonTarget::with('brand')->where('brand_id', $brand->id)->get();

        return ['objectifs' => $targets->map(function (CarbonTarget $target) {
            $status = $this->carbon->status($target);

            return [
                'objectif' => $target->name, 'saison' => $target->season, 'budget_kg' => $status['budget_kg'], 'emissions_prevues_kg' => $status['total_kg'],
                'consommation_pourcent' => $status['usage'], 'etat' => $status['state_label'], 'produits_au_dessus_du_plafond' => $status['over_unit'],
                'economie_possible_kg' => $status['savings_kg'], 'consommation_apres_leviers_pourcent' => $status['after_levers_usage'],
                'leviers' => $status['rows']->filter(fn ($row) => $row['lever'])->map(fn ($row) => $row['product']->name.' : '.$row['lever']['text'].' (−'.$row['lever']['saving_kg'].' kg)')->values()->all(),
            ];
        })->all()];
    }

    private function recycling(Brand $brand): array
    {
        $loops = RecyclingLoop::with('supplier')->where('brand_id', $brand->id)->get();
        $closed = $loops->where('status', 'closed');

        return [
            'demandes' => $loops->count(), 'en_cours' => $loops->filter->isOpen()->count(),
            'kg_collectes' => round($loops->whereNotIn('status', ['requested', 'proposed', 'accepted', 'declined'])->sum(fn ($l) => (float) ($l->received_kg ?: $l->totalKg())), 1),
            'kg_recycles' => round((float) $loops->whereIn('status', ['transformed', 'delivered', 'closed'])->sum('output_kg'), 1),
            'frais_payes_tnd' => round((float) $closed->where('mode', 'recover')->sum('amount'), 2),
            'avoirs_recus_tnd' => round(abs((float) $closed->where('mode', 'buyback')->sum('amount')), 2),
            'a_traiter_par_la_marque' => $loops->filter(fn ($l) => $l->nextActor() === 'brand')->map(fn ($l) => $l->reference.' ('.$l->supplier?->name.') : '.$l->nextAction())->values()->all(),
        ];
    }

    private function claim(string $text): array
    {
        if (trim($text) === '') {
            return ['erreur' => 'Texte vide.'];
        }
        $analysis = $this->claims->analyse($text);

        return [
            'risque' => $analysis['score'], 'niveau' => $analysis['level'], 'reglementation' => $analysis['regulation'],
            'phrases' => collect($analysis['sentences'])->map(fn ($row) => ['phrase' => $row['text'], 'verdict' => $row['label'], 'pourquoi' => $row['why'], 'correction' => $row['fix']])->all(),
        ];
    }
}
