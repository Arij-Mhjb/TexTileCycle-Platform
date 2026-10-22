<?php

namespace App\Services;

use App\Models\RecyclingLoop;
use App\Support\SimplePdf;
use Illuminate\Support\Facades\DB;

/**
 * Facture (la marque récupère la matière : le fournisseur facture les frais de recyclage)
 * ou avoir (le fournisseur rachète la matière : il crédite la marque), en PDF.
 * Les montants de la plateforme sont TTC ; la TVA tunisienne (19 %) est détaillée sur le document.
 */
class RecyclingInvoiceService
{
    public const VAT_RATE = 0.19;

    private const INK = [0.06, 0.09, 0.16];

    private const MUTED = [0.39, 0.45, 0.53];

    private const TEAL = [0.06, 0.46, 0.43];

    /** Numéro unique, attribué une seule fois quand le prix devient définitif. */
    public function assignNumber(RecyclingLoop $loop): string
    {
        if ($loop->invoice_number) {
            return $loop->invoice_number;
        }

        return DB::transaction(function () use ($loop) {
            $prefix = ($loop->isBuyback() ? 'AV' : 'FAC').'-RCY-'.now()->format('Y').'-';
            $last = RecyclingLoop::where('invoice_number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('invoice_number')->value('invoice_number');
            $number = $prefix.str_pad((string) ((int) substr((string) $last, -4) + 1), 4, '0', STR_PAD_LEFT);
            $loop->forceFill(['invoice_number' => $number, 'invoiced_at' => now()])->save();

            return $number;
        });
    }

    /** @return array{ttc: float, ht: float, vat: float} */
    public function totals(RecyclingLoop $loop): array
    {
        $ttc = round(abs((float) $loop->amount), 2);
        $ht = round($ttc / (1 + self::VAT_RATE), 2);

        return ['ttc' => $ttc, 'ht' => $ht, 'vat' => round($ttc - $ht, 2)];
    }

    public function pdf(RecyclingLoop $loop): string
    {
        $loop->loadMissing(['brand', 'supplier', 'outputMaterial', 'outputBatch', 'items']);
        $number = $this->assignNumber($loop);
        $pricing = $loop->pricing ?? [];
        $buyback = $loop->isBuyback();
        $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' TND';
        $kg = fn ($value) => number_format((float) $value, 1, ',', ' ').' kg';
        $pdf = new SimplePdf;

        // En-tête
        $pdf->rect(0, 0, SimplePdf::WIDTH, 92, [0.04, 0.33, 0.31]);
        $pdf->text(40, 42, 'TexTileCycle', 22, true, [1, 1, 1]);
        $pdf->text(40, 62, 'Seconde vie de la matière · recyclage marque - fournisseur', 9.5, false, [0.8, 0.95, 0.92]);
        $pdf->text(555, 40, $buyback ? 'AVOIR' : 'FACTURE', 22, true, [1, 1, 1], 'right');
        $pdf->text(555, 60, 'N° '.$number, 10, true, [0.8, 0.95, 0.92], 'right');
        $pdf->text(555, 76, 'Date : '.($loop->invoiced_at ?? now())->format('d/m/Y'), 9, false, [0.8, 0.95, 0.92], 'right');

        // Parties : le fournisseur émet le document, la marque le reçoit.
        $y = 128;
        foreach ([[40, 'ÉMETTEUR (FOURNISSEUR)', $loop->supplier], [315, 'DESTINATAIRE (MARQUE)', $loop->brand]] as [$x, $title, $party]) {
            $pdf->text($x, $y, $title, 8, true, self::MUTED);
            $pdf->text($x, $y + 18, (string) ($party->legal_name ?? $party->name), 12, true, self::INK);
            $address = collect([$party->city ?? null, $party->country ?? null])->filter()->join(', ');
            $pdf->text($x, $y + 33, $address !== '' ? $address : 'Tunisie', 9, false, self::MUTED);
            $email = $party->email ?? $party->contact_email ?? null;
            if (filled($email)) {
                $pdf->text($x, $y + 46, (string) $email, 9, false, self::MUTED);
            }
        }

        // Référence de la demande
        $y = 205;
        $pdf->rect(40, $y, 515, 40, [0.94, 0.99, 0.98]);
        $pdf->text(54, $y + 16, 'Demande de recyclage '.$loop->reference, 10, true, self::INK);
        $pdf->text(54, $y + 30, $kg($loop->received_kg ?: $loop->totalKg()).' reçus -> '.$kg($loop->output_kg).' de '.mb_strtolower((string) $loop->outputMaterial?->name)
            .' (rendement '.$loop->yieldRate().' %)'.($loop->outputBatch ? ' · lot traçable '.$loop->outputBatch->batch_code : ''), 9, false, self::MUTED);
        $pdf->text(541, $y + 16, $buyback ? 'Rachat par le fournisseur' : 'Récupération par la marque', 9, true, self::TEAL, 'right');

        // Lignes
        $y = 270;
        $pdf->text(40, $y, 'DÉSIGNATION', 8, true, self::MUTED);
        $pdf->text(300, $y, 'CALCUL', 8, true, self::MUTED);
        $pdf->text(555, $y, 'MONTANT TTC', 8, true, self::MUTED, 'right');
        $pdf->line(40, $y + 8, 555, $y + 8, [0.8, 0.84, 0.88]);
        $y += 26;
        foreach ($pricing['lines'] ?? [] as $line) {
            $pdf->text(40, $y, $line['label'], 10, false, self::INK);
            $pdf->text(300, $y, $line['detail'], 9, false, self::MUTED);
            $pdf->text(555, $y, ($buyback ? '- ' : '').$money($line['amount']), 10, false, self::INK, 'right');
            $pdf->line(40, $y + 9, 555, $y + 9);
            $y += 24;
        }
        if ($buyback) {
            $pdf->text(40, $y, 'Rachat de la matière recyclée', 10, false, self::INK);
            $pdf->text(300, $y, $kg($pricing['output_kg'] ?? 0).' × prix du marché × '.(int) (RecyclingPricing::BUYBACK_SHARE * 100).' %', 9, false, self::MUTED);
            $pdf->text(555, $y, '+ '.$money($pricing['material_value'] ?? 0), 10, false, self::TEAL, 'right');
            $pdf->line(40, $y + 9, 555, $y + 9);
            $y += 24;
        }

        // Totaux
        $totals = $this->totals($loop);
        $y += 10;
        foreach ([['Total HT', $totals['ht']], ['TVA '.(int) (self::VAT_RATE * 100).' %', $totals['vat']]] as [$label, $value]) {
            $pdf->text(420, $y, $label, 10, false, self::MUTED);
            $pdf->text(555, $y, $money($value), 10, false, self::INK, 'right');
            $y += 18;
        }
        $pdf->rect(300, $y - 4, 255, 36, [0.04, 0.33, 0.31]);
        $pdf->text(314, $y + 18, $buyback ? 'Avoir en faveur de la marque' : 'Net à payer par la marque', 11, true, [1, 1, 1]);
        $pdf->text(541, $y + 18, $money($totals['ttc']), 13, true, [1, 1, 1], 'right');
        $y += 64;

        // Économie et conditions
        if (! $buyback && ($pricing['savings'] ?? 0) > 0) {
            $y = $pdf->paragraph(40, $y, 515, 'Économie pour la marque : acheter '.$kg($pricing['output_kg'] ?? 0).' de matière neuve coûterait '
                .$money($pricing['new_value'] ?? 0).', soit '.$money($pricing['savings']).' économisés grâce au recyclage.', 9.5, true, self::TEAL) + 6;
        }
        $y = $pdf->paragraph(40, $y, 515, $buyback
            ? 'Cet avoir est à déduire des prochains paiements de la marque au fournisseur. Il correspond à la valeur de rachat de la matière, frais de recyclage déduits.'
            : 'Paiement à 30 jours à réception de la matière recyclée. Prix calculé automatiquement selon le barème TexTileCycle (collecte, démontage, recyclage au kilo), sur le poids réellement pesé chez le fournisseur.', 9);

        // Pied de page
        $pdf->line(40, 790, 555, 790);
        $pdf->text(40, 806, 'Document généré par TexTileCycle le '.now()->format('d/m/Y à H:i').' · '.$number, 8, false, self::MUTED);
        $pdf->text(555, 806, 'Projet académique · Esprit 5TWIN4', 8, false, self::MUTED, 'right');

        return $pdf->output();
    }
}
