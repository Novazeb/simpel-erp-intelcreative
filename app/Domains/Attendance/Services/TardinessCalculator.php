<?php

namespace App\Domains\Attendance\Services;

use Carbon\Carbon;

class TardinessCalculator
{
    /**
     * Hitung keterlambatan berdasarkan work shift dan jam clock in.
     * Sesuai TC-ATT-01: Shift 09:00, toleransi 15 mnt.
     * 09:14 -> 0 mnt telat.
     * 09:16 -> 16 mnt telat (bukan 1 mnt).
     */
    public function calculateLateMinutes(string $shiftStartTime, int $lateToleranceMinutes, Carbon $clockIn): int
    {
        $shiftStart = Carbon::parse($clockIn->toDateString().' '.$shiftStartTime);

        if ($clockIn->lessThanOrEqualTo($shiftStart)) {
            return 0;
        }

        $differenceInMinutes = $shiftStart->diffInMinutes($clockIn, false);

        if ($differenceInMinutes <= $lateToleranceMinutes) {
            return 0;
        }

        return (int) $differenceInMinutes;
    }
}
