<?php

namespace App\Console\Commands;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\HR\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseDailyAttendanceWindow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:close-daily-window';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tutup window presensi harian pada 09:26 WIB dan tandai karyawan tanpa kabar sebagai ABSENT';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        // Ambil semua karyawan aktif (bukan soft-deleted, belum resign, dan bergabung sebelum hari ini)
        $activeEmployees = Employee::whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('employment_status')
                    ->orWhere('employment_status', '!=', 'RESIGNED');
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('join_date')
                    ->orWhereDate('join_date', '<', $today);
            })
            ->get();

        $absentCount = 0;
        foreach ($activeEmployees as $emp) {
            $hasAttendance = Attendance::where('employee_id', $emp->id)
                ->where('work_date', $today)
                ->exists();

            if ($hasAttendance) {
                continue;
            }

            // Cek apakah karyawan sedang dalam cuti resmi yang telah disetujui
            $hasApprovedLeave = LeaveRequest::where('employee_id', $emp->id)
                ->where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($hasApprovedLeave) {
                Attendance::create([
                    'employee_id' => $emp->id,
                    'work_date' => $today,
                    'clock_in' => null,
                    'status' => 'ON_LEAVE',
                    'late_minutes' => 0,
                    'attendance_method' => 'SYSTEM_AUTO',
                    'notes' => 'Status ON_LEAVE otomatis dari pengajuan cuti yang disetujui',
                ]);

                continue;
            }

            // Tandai mangkir (ABSENT)
            Attendance::create([
                'employee_id' => $emp->id,
                'work_date' => $today,
                'clock_in' => null,
                'status' => 'ABSENT',
                'late_minutes' => 0,
                'attendance_method' => 'SYSTEM_AUTO',
                'notes' => 'Otomatis ditandai Mangkir oleh sistem pada pukul 09:26 WIB',
            ]);
            $absentCount++;
        }

        $this->info("Window presensi ditutup. Total {$absentCount} karyawan ditandai ABSENT hari ini.");

        return Command::SUCCESS;
    }
}
