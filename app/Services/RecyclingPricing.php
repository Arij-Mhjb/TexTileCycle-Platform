<?php

namespace App\Services;

/**
 * Barème de la boucle de recyclage (indicatif, en TND). Le prix est calculé automatiquement :
 * personne ne le saisit, la marque et le fournisseur voient le même détail.
 *
 *  - Frais de recyclage = collecte + démontage des pièces défectueuses + recyclage au kilo.
 *  - « Récupérer la matière » : la marque paie les frais et reçoit la matière recyclée
 *    (on lui montre l'économie par rapport à l'achat de matière neuve).
 *  - « Le fournisseur rachète » : il garde la matière et verse à la marque un avoir
 *    = valeur de rachat de la matière − frais de recyclage (jamais négatif).
 */
class RecyclingPricing
{
    /** Collecte et transport : forfait + par kilo envoyé. */
    public const COLLECTION_FLAT = 15.0;

    public const COLLECTION_PER_KG = 0.10;

    /** Démontage d'une pièce défectueuse (boutons, fermetures, étiquettes). */
    public const DISMANTLING_PER_PIECE = 0.25;

    /** Recyclage (tri, effilochage, filature) par kilo envoyé, selon la matière produite. */
    public const RECYCLING_PER_KG = [
        'Coton recyclé' => 1.20, 'Polyester recyclé' => 1.50, 'Polyamide recyclé' => 1.80,
        'Laine recyclée' => 2.20, 'Caoutchouc recyclé' => 1.00,
    ];

    /** Prix de marché d'un kilo de matière recyclée. */
    public const RECYCLED_PRICE = [
        'Coton recyclé' => 6.50, 'Polyester recyclé' => 5.80, 'Polyamide recyclé' => 7.50,
        'Laine recyclée' => 12.00, 'Caoutchouc recyclé' => 3.00,
    ];

    /** Prix d'un kilo de la même matière achetée neuve (pour montrer l'économie). */
    public const NEW_PRICE = [
        'Coton recyclé' => 9.00, 'Polyester recyclé' => 7.20, 'Polyamide recyclé' => 9.50,
        'Laine recyclée' => 24.00, 'Caoutchouc recyclé' => 4.50,
    ];

    /** Part de la valeur de marché que le fournisseur reverse quand il garde la matière. */
    public const BUYBACK_SHARE = 0.60;

    private const DEFAULT_RECYCLING = 1.40;

    private const DEFAULT_RECYCLED_PRICE = 5.00;

    private const DEFAULT_NEW_PRICE = 8.00;

    /**
     * @return array{mode: string, input_kg: float, output_kg: float, pieces: int, material: string,
     *   lines: array<int, array{label: string, detail: string, amount: float}>, fees: float,
     *   material_value: float, new_value: float, savings: float, credit: float, amount: float}
     *   amount > 0 : la marque paie ; amount < 0 : la marque reçoit.
     */
    public function quote(string $mode, float $inputKg, int $defectivePieces, string $material, float $outputKg): array
    {
        $recyclingRate = self::RECYCLING_PER_KG[$material] ?? self::DEFAULT_RECYCLING;
        $lines = [
            ['label' => 'Collecte et transport', 'detail' => $this->money(self::COLLECTION_FLAT).' + '.$this->money(self::COLLECTION_PER_KG).' × '.$this->kg($inputKg), 'amount' => round(self::COLLECTION_FLAT + self::COLLECTION_PER_KG * $inputKg, 2)],
        ];
        if ($defectivePieces > 0) {
            $lines[] = ['label' => 'Démontage des pièces', 'detail' => $defectivePieces.' pièce(s) × '.$this->money(self::DISMANTLING_PER_PIECE), 'amount' => round($defectivePieces * self::DISMANTLING_PER_PIECE, 2)];
        }
        $lines[] = ['label' => 'Recyclage en '.mb_strtolower($material), 'detail' => $this->kg($inputKg).' × '.$this->money($recyclingRate), 'amount' => round($inputKg * $recyclingRate, 2)];

        $fees = round(array_sum(array_column($lines, 'amount')), 2);
        $marketValue = round($outputKg * (self::RECYCLED_PRICE[$material] ?? self::DEFAULT_RECYCLED_PRICE), 2);
        $newValue = round($outputKg * (self::NEW_PRICE[$material] ?? self::DEFAULT_NEW_PRICE), 2);
        $credit = $mode === 'buyback' ? max(0.0, round($marketValue * self::BUYBACK_SHARE - $fees, 2)) : 0.0;

        return [
            'mode' => $mode,
            'input_kg' => round($inputKg, 2),
            'output_kg' => round($outputKg, 2),
            'pieces' => $defectivePieces,
            'material' => $material,
            'lines' => $lines,
            'fees' => $fees,
            'material_value' => $mode === 'buyback' ? round($marketValue * self::BUYBACK_SHARE, 2) : $marketValue,
            'new_value' => $newValue,
            'savings' => $mode === 'recover' ? round(max(0, $newValue - $fees), 2) : 0.0,
            'credit' => $credit,
            'amount' => $mode === 'recover' ? $fees : -$credit,
        ];
    }

    /** Barème exposé au formulaire pour l'estimation en direct (mêmes chiffres que le serveur). */
    public static function tariff(): array
    {
        return [
            'collection_flat' => self::COLLECTION_FLAT, 'collection_per_kg' => self::COLLECTION_PER_KG,
            'dismantling_per_piece' => self::DISMANTLING_PER_PIECE, 'recycling_per_kg' => self::RECYCLING_PER_KG,
            'recycled_price' => self::RECYCLED_PRICE, 'new_price' => self::NEW_PRICE, 'buyback_share' => self::BUYBACK_SHARE,
            'defaults' => ['recycling' => self::DEFAULT_RECYCLING, 'recycled_price' => self::DEFAULT_RECYCLED_PRICE, 'new_price' => self::DEFAULT_NEW_PRICE],
        ];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ').' TND';
    }

    private function kg(float $value): string
    {
        return number_format($value, 1, ',', ' ').' kg';
    }
}
