<?php

namespace App\Domains\Attendance\Actions;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\Attendance\Services\TardinessCalculator;
use App\Domains\HR\Models\Employee;
use Carbon\Carbon;

class RecordAttendanceAction
{
    public function __construct(
        protected TardinessCalculator $tardinessCalculator
    ) {}

    public function execute(Employee $employee, Carbon $timestamp): Attendance
    {
        $employee->loadMissing('workShift');
        $shift = $employee->workShift;
        $workDate = $timestamp->toDateString();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
        ]);

        if (! $attendance->exists) {
            // Clock In
            $lateMinutes = 0;
            if ($shift) {
                $lateMinutes = $this->tardinessCalculator->calculateLateMinutes(
                    $shift->start_time,
                    $shift->late_tolerance_minutes ?? 0,
                    $timestamp
                );
            }

            $attendance->clock_in = $timestamp;
            $attendance->late_minutes = $lateMinutes;
            $attendance->status = $lateMinutes > 0 ? 'LATE' : 'PRESENT';
            $attendance->save();

            return $attendance;
        }

        // Clock Out
        $attendance->clock_out = $timestamp;

        // Hitung overtime jika clock out melebihi shift end_time
        if ($shift) {
            $shiftEnd = Carbon::parse($workDate.' '.$shift->end_time);
            if ($timestamp->greaterThan($shiftEnd)) {
                $overtimeMinutes = $shiftEnd->diffInMinutes($timestamp);
                $attendance->overtime_hours = round($overtimeMinutes / 60, 2);
            }
        }

        $attendance->save();

        return $attendance;
    }
}
