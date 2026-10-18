<?php

namespace App\Support;

/**
 * Petit moteur isométrique pour les illustrations 3D des modules :
 * projette des blocs (x, y, z, largeur, profondeur, hauteur) en trois faces SVG
 * (dessus clair, côté gauche moyen, côté droit sombre).
 */
class Isometric
{
    public const SCALE = 19;

    public const ORIGIN_X = 140;

    public const ORIGIN_Y = 112;

    public static function point(float $x, float $y, float $z): string
    {
        return round(self::ORIGIN_X + ($x - $y) * 0.866 * self::SCALE, 1).','.round(self::ORIGIN_Y + ($x + $y) * 0.5 * self::SCALE - $z * self::SCALE, 1);
    }

    /** @param  array{0: string, 1: string, 2: string}  $colors  dessus, côté gauche, côté droit */
    public static function box(float $x, float $y, float $z, float $w, float $d, float $h, array $colors): string
    {
        return self::polygon([[$x, $y + $d, $z], [$x + $w, $y + $d, $z], [$x + $w, $y + $d, $z + $h], [$x, $y + $d, $z + $h]], $colors[1])
            .self::polygon([[$x + $w, $y, $z], [$x + $w, $y + $d, $z], [$x + $w, $y + $d, $z + $h], [$x + $w, $y, $z + $h]], $colors[2])
            .self::polygon([[$x, $y, $z + $h], [$x + $w, $y, $z + $h], [$x + $w, $y + $d, $z + $h], [$x, $y + $d, $z + $h]], $colors[0]);
    }

    public static function ground(float $size = 10): string
    {
        $half = $size / 2;

        return '<polygon points="'.implode(' ', [self::point(-$half, -$half, 0), self::point($half, -$half, 0), self::point($half, $half, 0), self::point(-$half, $half, 0)])
            .'" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.25)" stroke-dasharray="3 4"/>';
    }

    /** @param  array<int, array{0: float, 1: float, 2: float}>  $points */
    private static function polygon(array $points, string $fill): string
    {
        return '<polygon points="'.implode(' ', array_map(fn (array $p) => self::point(...$p), $points)).'" fill="'.$fill.'" stroke="rgba(255,255,255,.18)" stroke-width=".6" stroke-linejoin="round"/>';
    }
}
