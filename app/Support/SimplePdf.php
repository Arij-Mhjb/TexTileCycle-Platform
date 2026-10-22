<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Générateur PDF minimal (une page A4, polices Helvetica standard) : textes, rectangles et traits.
 * Sans dépendance : suffisant pour des documents simples comme une facture.
 * Coordonnées en points, origine en haut à gauche.
 */
class SimplePdf
{
    public const WIDTH = 595;

    public const HEIGHT = 842;

    private const WIDTHS = [
        'regular' => [278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556, 1015,
            667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556, 333,
            556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584],
        'bold' => [278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611, 975,
            722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556, 333,
            556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611, 611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584],
    ];

    private string $content = '';

    /** @param array{0: float, 1: float, 2: float} $color RVB de 0 à 1 */
    public function text(float $x, float $y, string $text, float $size = 10, bool $bold = false, array $color = [0.06, 0.09, 0.16], string $align = 'left'): self
    {
        $width = $this->width($text, $size, $bold);
        $x = match ($align) {
            'right' => $x - $width,
            'center' => $x - $width / 2,
            default => $x,
        };
        $this->content .= sprintf("BT /%s %.1f Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET\n",
            $bold ? 'F2' : 'F1', $size, ...[...$color, $x, self::HEIGHT - $y, $this->escape($text)]);

        return $this;
    }

    /** Texte coupé en lignes de largeur maximale ; retourne la position verticale après le bloc. */
    public function paragraph(float $x, float $y, float $maxWidth, string $text, float $size = 9, bool $bold = false, array $color = [0.28, 0.33, 0.41], float $leading = 1.35): float
    {
        $line = '';
        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $this->width($candidate, $size, $bold) > $maxWidth) {
                $this->text($x, $y, $line, $size, $bold, $color);
                $y += $size * $leading;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') {
            $this->text($x, $y, $line, $size, $bold, $color);
            $y += $size * $leading;
        }

        return $y;
    }

    public function rect(float $x, float $y, float $width, float $height, array $fill): self
    {
        $this->content .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n", ...[...$fill, $x, self::HEIGHT - $y - $height, $width, $height]);

        return $this;
    }

    public function line(float $x1, float $y1, float $x2, float $y2, array $color = [0.89, 0.91, 0.94], float $width = 0.8): self
    {
        $this->content .= sprintf("%.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S\n", ...[...$color, $width, $x1, self::HEIGHT - $y1, $x2, self::HEIGHT - $y2]);

        return $this;
    }

    public function width(string $text, float $size, bool $bold = false): float
    {
        $table = self::WIDTHS[$bold ? 'bold' : 'regular'];
        $total = 0;
        foreach (mb_str_split(Str::ascii($this->clean($text))) as $char) {
            $code = ord($char);
            $total += $table[$code - 32] ?? 556;
        }

        return $total * $size / 1000;
    }

    public function output(): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::WIDTH.' '.self::HEIGHT.'] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($this->content)." >>\nstream\n".$this->content.'endstream',
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    /** Caractères hors Windows-1252 remplacés par un équivalent lisible. */
    private function clean(string $text): string
    {
        return strtr($text, ['₂' => '2', '≈' => '~', '→' => '->', '−' => '-', "\u{202F}" => ' ', "\u{00A0}" => ' ', '⇄' => '<->', '✓' => 'OK']);
    }

    private function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $this->clean($text));

        return strtr($encoded === false ? Str::ascii($text) : $encoded, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    }
}
