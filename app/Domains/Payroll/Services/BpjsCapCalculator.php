<?php

namespace App\Domains\Payroll\Services;

class BpjsCapCalculator
{
    /**
     * Plafon Upah Tertinggi BPJS Kesehatan: Rp 12.000.000 (umpanbalik.md Bagian 3.C)
     * Porsi Karyawan = 1%
     */
    public const BPJS_KES_MAX_BASE = 12000000.00;
    public const BPJS_KES_RATE_EMPLOYEE = 0.01;

    /**
     * Plafon Upah Tertinggi BPJS Ketenagakerjaan Jaminan Pensiun (JP) tahun berjalan: ~Rp 10.042.300
     * JHT Karyawan: 2% (tanpa cap), JP Karyawan: 1% (capped)
     */
    public const BPJS_TK_JP_MAX_BASE = 10042300.00;
    public const BPJS_TK_JHT_RATE = 0.02;
    public const BPJS_TK_JP_RATE = 0.01;

    public function calculateBpjsKesehatan(float $basicSalary): float
    {
        $base = min($basicSalary, self::BPJS_KES_MAX_BASE);

        return round($base * self::BPJS_KES_RATE_EMPLOYEE, 2);
    }

    public function calculateBpjsKetenagakerjaan(float $basicSalary): float
    {
        $jht = $basicSalary * self::BPJS_TK_JHT_RATE;
        $jpBase = min($basicSalary, self::BPJS_TK_JP_MAX_BASE);
        $jp = $jpBase * self::BPJS_TK_JP_RATE;

        return round($jht + $jp, 2);
    }
}

