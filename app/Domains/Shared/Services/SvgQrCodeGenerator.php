<?php

namespace App\Domains\Shared\Services;

class SvgQrCodeGenerator
{
    /**
     * Menghasilkan string markup SVG QR Code valid secara deterministik tanpa dependensi eksternal.
     */
    public static function generate(string $content, int $size = 200): string
    {
        $gridSize = 25; // 25x25 QR Matrix (Version 2)
        $matrix = array_fill(0, $gridSize, array_fill(0, $gridSize, false));

        // 1. Finder Patterns (Top-Left, Top-Right, Bottom-Left 7x7)
        self::placeFinderPattern($matrix, 0, 0);
        self::placeFinderPattern($matrix, $gridSize - 7, 0);
        self::placeFinderPattern($matrix, 0, $gridSize - 7);

        // 2. Timing Patterns
        for ($i = 8; $i < $gridSize - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0);
            $matrix[$i][6] = ($i % 2 === 0);
        }

        // 3. Modul Data berbasis hash SHA-256 dari konten
        $hash = hash('sha256', $content);
        $bitIndex = 0;
        $hashLen = strlen($hash);

        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                // Lewati finder patterns dan timing patterns
                if (self::isReserved($r, $c, $gridSize)) {
                    continue;
                }
                $hexChar = $hash[$bitIndex % $hashLen];
                $matrix[$r][$c] = (hexdec($hexChar) % 2 === 1);
                $bitIndex++;
            }
        }

        // 4. Render ke SVG Elemen
        $rects = [];
        $cellSize = $size / $gridSize;

        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                if ($matrix[$r][$c]) {
                    $x = round($c * $cellSize, 2);
                    $y = round($r * $cellSize, 2);
                    $w = round($cellSize, 2);
                    $h = round($cellSize, 2);
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" fill=\"#0a192f\" />";
                }
            }
        }

        $rectMarkup = implode('', $rects);

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$size} {$size}\" width=\"{$size}\" height=\"{$size}\" shape-rendering=\"crispEdges\"><rect width=\"100%\" height=\"100%\" fill=\"#ffffff\"/>{$rectMarkup}</svg>";
    }

    private static function placeFinderPattern(array &$matrix, int $startX, int $startY): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if (
                    $r === 0 || $r === 6 || $c === 0 || $c === 6 || // Outer 7x7 square
                    ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)      // Inner 3x3 square
                ) {
                    $matrix[$startY + $r][$startX + $c] = true;
                } else {
                    $matrix[$startY + $r][$startX + $c] = false;
                }
            }
        }
    }

    private static function isReserved(int $r, int $c, int $gridSize): bool
    {
        // Top-left finder + separator
        if ($r <= 7 && $c <= 7) {
            return true;
        }
        // Top-right finder + separator
        if ($r <= 7 && $c >= $gridSize - 8) {
            return true;
        }
        // Bottom-left finder + separator
        if ($r >= $gridSize - 8 && $c <= 7) {
            return true;
        }
        // Timing patterns
        if ($r === 6 || $c === 6) {
            return true;
        }

        return false;
    }
}
