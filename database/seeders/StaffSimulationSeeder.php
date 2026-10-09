<?php

namespace Database\Seeders;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Services\PayrollCalculationEngine;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSimulationSeeder extends Seeder
{
    /**
     * Run the database seeds for staff simulation (Rian Ardiansyah & Dewi Safitri).
     */
    public function run(): void
    {
        // 1. Departemen & Jabatan
        $deptCrt = Department::firstOrCreate(['code' => 'CRT'], ['name' => 'Creative & Media']);
        $deptTech = Department::firstOrCreate(['code' => 'TECH'], ['name' => 'Technology & Engineering']);

        $desigRian = Designation::firstOrCreate(
            ['department_id' => $deptCrt->id, 'title' => 'Graphic & UI Designer']
        );

        $desigDewi = Designation::firstOrCreate(
            ['department_id' => $deptTech->id, 'title' => 'Fullstack Web Developer']
        );

        $shift = WorkShift::firstOrCreate(
            ['name' => 'Shift Reguler'],
            [
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'late_tolerance_minutes' => 15,
            ]
        );

        // 2. Akun & Profil Karyawan: Rian Ardiansyah
        $userRian = User::firstOrCreate(
            ['email' => 'rian.ardiansyah@intelcreative.co.id'],
            [
                'name' => 'Rian Ardiansyah',
                'password' => Hash::make('SecretPassword123!'),
            ]
        );
        $userRian->syncRoles(['employee']);

        $empRian = Employee::firstOrCreate(
            ['email' => 'rian.ardiansyah@intelcreative.co.id'],
            [
                'nik' => 'ITC-STF-002',
                'full_name' => 'Rian Ardiansyah',
                'phone' => '081234567891',
                'department_id' => $deptCrt->id,
                'designation_id' => $desigRian->id,
                'work_shift_id' => $shift->id,
                'employment_status' => 'PKWTT',
                'join_date' => '2026-01-15',
                'bank_name' => 'BCA',
                'bank_account_number' => '5271892011',
                'bank_account_holder' => 'Rian Ardiansyah',
                'basic_salary' => 9500000.00,
            ]
        );

        // 3. Akun & Profil Karyawan: Dewi Safitri
        $userDewi = User::firstOrCreate(
            ['email' => 'dewi.safitri@intelcreative.co.id'],
            [
                'name' => 'Dewi Safitri',
                'password' => Hash::make('SecretPassword123!'),
            ]
        );
        $userDewi->syncRoles(['employee']);

        $empDewi = Employee::firstOrCreate(
            ['email' => 'dewi.safitri@intelcreative.co.id'],
            [
                'nik' => 'ITC-STF-003',
                'full_name' => 'Dewi Safitri',
                'phone' => '081234567892',
                'department_id' => $deptTech->id,
                'designation_id' => $desigDewi->id,
                'work_shift_id' => $shift->id,
                'employment_status' => 'PKWTT',
                'join_date' => '2026-02-01',
                'bank_name' => 'BCA',
                'bank_account_number' => '5271892022',
                'bank_account_holder' => 'Dewi Safitri',
                'basic_salary' => 11000000.00,
            ]
        );

        // 4. Riwayat Presensi Bulan September 2026 (Untuk perhitungan gaji dan snapshot)
        $dateRange = CarbonPeriod::create('2026-09-01', '2026-09-30');
        foreach ($dateRange as $dt) {
            if (! $dt->isWeekend()) {
                $dateStr = $dt->toDateString();

                // Presensi Rian (Masuk 08:50, Pulang 18:30 -> lembur ringan di hari kerja)
                Attendance::firstOrCreate(
                    [
                        'employee_id' => $empRian->id,
                        'work_date' => $dateStr,
                    ],
                    [
                        'clock_in' => Carbon::parse("{$dateStr} 08:50:00"),
                        'clock_out' => Carbon::parse("{$dateStr} 18:30:00"),
                        'late_minutes' => 0,
                        'early_leave_minutes' => 0,
                        'overtime_hours' => 0.50,
                        'status' => 'PRESENT',
                    ]
                );

                // Presensi Dewi (Masuk 08:55, Pulang 18:45)
                Attendance::firstOrCreate(
                    [
                        'employee_id' => $empDewi->id,
                        'work_date' => $dateStr,
                    ],
                    [
                        'clock_in' => Carbon::parse("{$dateStr} 08:55:00"),
                        'clock_out' => Carbon::parse("{$dateStr} 18:45:00"),
                        'late_minutes' => 0,
                        'early_leave_minutes' => 0,
                        'overtime_hours' => 0.75,
                        'status' => 'PRESENT',
                    ]
                );
            }
        }

        // 5. Periode Payroll September 2026 & Perhitungan Slip Gaji Resmi
        $period = PayrollPeriod::firstOrCreate(
            ['name' => 'Payroll September 2026'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'cutoff_date' => '2026-09-30',
                'payment_date' => '2026-10-01',
                'status' => 'APPROVED',
            ]
        );

        // Eksekusi engine kalkulasi slip gaji untuk Rian dan Dewi
        $engine = app(PayrollCalculationEngine::class);
        $engine->calculateForEmployee($period, $empRian);
        $engine->calculateForEmployee($period, $empDewi);
    }
}
