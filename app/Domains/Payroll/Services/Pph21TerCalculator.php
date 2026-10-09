<?php

namespace App\Domains\Payroll\Services;

class Pph21TerCalculator
{
    /**
     * Menghitung PPh 21 menggunakan Tarif Efektif Rata-Rata (TER)
     * PP 58/2023 & PMK 168/2023
     *
     * Kategori A: TK/0 (54 jt), TK/1 (58.5 jt), K/0 (58.5 jt)
     * Kategori B: TK/2, TK/3, K/1, K/2
     * Kategori C: K/3
     */
    public function calculateMonthlyTax(float $grossIncome, string $taxStatus = 'TK/0'): float
    {
        if ($grossIncome <= 5400000) {
            return 0.00; // PTKP bulanan dasar bebas pajak
        }

        $category = $this->determineCategory($taxStatus);
        $rate = $this->getEffectiveRate($category, $grossIncome);

        return round($grossIncome * $rate, 2);
    }

    protected function determineCategory(string $taxStatus): string
    {
        return match (strtoupper($taxStatus)) {
            'TK/0', 'TK/1', 'K/0' => 'A',
            'TK/2', 'TK/3', 'K/1', 'K/2' => 'B',
            'K/3' => 'C',
            default => 'A',
        };
    }

    protected function getEffectiveRate(string $category, float $gross): float
    {
        // Skema tarif TER Kategori A (PMK 168/2023)
        if ($category === 'A') {
            return match (true) {
                $gross <= 5400000 => 0.00,
                $gross <= 5650000 => 0.0025,
                $gross <= 5950000 => 0.005,
                $gross <= 6300000 => 0.0075,
                $gross <= 6750000 => 0.01,
                $gross <= 7500000 => 0.0125,
                $gross <= 8550000 => 0.015,
                $gross <= 9650000 => 0.0175,
                $gross <= 10050000 => 0.02,
                $gross <= 10350000 => 0.0225,
                $gross <= 10700000 => 0.025,
                $gross <= 11050000 => 0.03,
                $gross <= 11600000 => 0.035,
                $gross <= 12500000 => 0.04,
                $gross <= 13750000 => 0.05,
                $gross <= 15100000 => 0.06,
                $gross <= 16950000 => 0.07,
                $gross <= 19750000 => 0.08,
                $gross <= 24150000 => 0.09,
                default => 0.10,
            };
        }

        // Kategori B & C (simplified tier)
        if ($gross <= 6200000) {
            return 0.00;
        } elseif ($gross <= 10000000) {
            return 0.015;
        } elseif ($gross <= 15000000) {
            return 0.035;
        } else {
            return 0.06;
        }
    }
}
