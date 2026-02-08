<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Generate color shades from a base hex color
     *
     * @param string $hexColor Base color in hex format (#rrggbb)
     * @return array Array of color shades (50-900)
     */
    public static function generateShades(string $hexColor): array
    {
        // Remove # if present
        $hex = ltrim($hexColor, '#');

        // Convert hex to RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Convert RGB to HSL
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }

        $h = round($h * 360);
        $s = round($s * 100);
        $l = round($l * 100);

        // Generate shades by adjusting lightness
        return [
            '50' => self::hslToHex($h, max(0, $s - 20), min(100, $l + 40)),
            '100' => self::hslToHex($h, max(0, $s - 10), min(100, $l + 30)),
            '200' => self::hslToHex($h, $s, min(100, $l + 20)),
            '300' => self::hslToHex($h, $s, min(100, $l + 10)),
            '400' => self::hslToHex($h, $s, min(100, $l + 5)),
            '500' => $hexColor,
            '600' => self::hslToHex($h, $s, max(0, $l - 10)),
            '700' => self::hslToHex($h, $s, max(0, $l - 20)),
            '800' => self::hslToHex($h, $s, max(0, $l - 30)),
            '900' => self::hslToHex($h, $s, max(0, $l - 40)),
        ];
    }

    /**
     * Convert HSL to Hex color
     *
     * @param int $h Hue (0-360)
     * @param int $s Saturation (0-100)
     * @param int $l Lightness (0-100)
     * @return string Hex color (#rrggbb)
     */
    private static function hslToHex(int $h, int $s, int $l): string
    {
        $s = $s / 100;
        $l = $l / 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            $r = $c; $g = $x; $b = 0;
        } elseif ($h < 120) {
            $r = $x; $g = $c; $b = 0;
        } elseif ($h < 180) {
            $r = 0; $g = $c; $b = $x;
        } elseif ($h < 240) {
            $r = 0; $g = $x; $b = $c;
        } elseif ($h < 300) {
            $r = $x; $g = 0; $b = $c;
        } else {
            $r = $c; $g = 0; $b = $x;
        }

        $r = round(($r + $m) * 255);
        $g = round(($g + $m) * 255);
        $b = round(($b + $m) * 255);

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}
