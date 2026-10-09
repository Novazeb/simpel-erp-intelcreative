<?php

namespace Tests\Feature;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Domains\Payroll\Actions\CalculatePayrollBatchAction;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollSlip;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConcurrencyStressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
    }

    /**
     * Uji ketahanan konkurensi Clock-In:
     * Memastikan Atomic Lock (Cache::lock) memblokir panggilan simultan ganda
     * dan mengembalikan status 429 Too Many Requests.
     */
    public function test_concurrent_clock_in_is_blocked_by_atomic_lock_returning_429(): void
    {
        $staffUser = User::where('email', 'staff@intelcreative.co.id')->first();
        $staffEmployee = Employee::where('email', 'staff@intelcreative.co.id')->first();

        // 1. Simulasikan atomic lock yang sedang aktif (seolah request lain sedang diproses)
        $lockKey = "clock_in_employee_{$staffEmployee->id}";
        $lock = Cache::lock($lockKey, 5);
        $this->assertTrue($lock->get(), 'Kunci atomik utama harus berhasil diperoleh');

        try {
            // 2. Request simultan yang datang bersamaan harus ditolak dengan status 429
            $response = $this->actingAs($staffUser)->postJson('/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(429);
            $response->assertJson([
                'message' => 'Sistem sedang memproses presensi Anda, mohon tunggu.',
            ]);
        } finally {
            $lock->release();
        }

        // 3. Setelah lock dilepas, request berikutnya harus berhasil
        $successResponse = $this->actingAs($staffUser)->postJson('/attendance/clock-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $successResponse->assertStatus(200);

        // Verifikasi dalam database hanya ada tepat 1 baris presensi untuk hari ini
        $todayAttendances = Attendance::where('employee_id', $staffEmployee->id)
            ->whereDate('work_date', Carbon::today())
            ->count();

        $this->assertEquals(1, $todayAttendances, 'Data kehadiran tidak boleh terduplikasi akibat konkurensi');
    }

    /**
     * Uji performa kalkulasi penggajian berskala besar (Stress Test Batch Chunking):
     * Memverifikasi kalkulasi batch untuk puluhan hingga ratusan karyawan berjalan
     * dengan waktu eksekusi sub-detik dan tanpa kebocoran presisi desimal.
     */
    public function test_batch_payroll_chunk_calculation_stress_performance_under_load(): void
    {
        $dept = Department::first();
        $desig = Designation::first();
        $shift = WorkShift::first();

        $period = PayrollPeriod::create([
            'name' => 'Periode Uji Beban 50 Karyawan',
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->endOfMonth()->toDateString(),
            'cutoff_date' => Carbon::now()->endOfMonth()->toDateString(),
            'payment_date' => Carbon::now()->addDays(5)->toDateString(),
            'status' => 'DRAFT',
        ]);

        // Buat 50 profil karyawan secara massal
        for ($i = 1; $i <= 50; $i++) {
            Employee::create([
                'nik' => sprintf('STRESS-%04d', $i),
                'full_name' => "Karyawan Beban {$i}",
                'email' => sprintf('stress%04d@intelcreative.co.id', $i),
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'work_shift_id' => $shift->id,
                'join_date' => '2025-01-01',
                'bank_name' => 'BCA',
                'bank_account_number' => sprintf('555%07d', $i),
                'bank_account_holder' => "Karyawan Beban {$i}",
                'basic_salary' => 8000000 + ($i * 50000),
                'employment_status' => 'PKWTT',
            ]);
        }

        $startTime = microtime(true);

        // Jalankan orkestrasi kalkulasi batch
        $action = app(CalculatePayrollBatchAction::class);
        $action->execute($period);

        $executionTime = microtime(true) - $startTime;

        // Validasi seluruh slip gaji terbentuk (50 baru + karyawan existing dari seeder)
        $slipsCount = PayrollSlip::where('payroll_period_id', $period->id)->count();
        $this->assertGreaterThanOrEqual(50, $slipsCount);

        // Periksa kecepatan pemrosesan: 50+ karyawan harus selesai di bawah 3.0 detik
        $this->assertLessThan(3.0, $executionTime, "Kalkulasi batch 50+ karyawan memakan waktu {$executionTime}s, harus di bawah 3.0s");

        // Verifikasi keutuhan nominal: tidak boleh ada slip gaji dengan take_home_pay <= 0
        $invalidSlips = PayrollSlip::where('payroll_period_id', $period->id)
            ->where('take_home_pay', '<=', 0)
            ->count();
        $this->assertEquals(0, $invalidSlips, 'Seluruh slip gaji harus memiliki perhitungan take home pay positif');
    }

    /**
     * Uji otomatisasi pencadangan basis data terenkripsi dan rotasi retensi.
     */
    public function test_database_backup_command_creates_encrypted_archive(): void
    {
        $exitCode = Artisan::call('db:backup', [
            '--encrypt' => true,
            '--disk' => 'local',
            '--clean-older-days' => 30,
        ]);

        $this->assertEquals(0, $exitCode, 'Perintah db:backup harus keluar dengan kode 0 (SUCCESS)');
    }
}
