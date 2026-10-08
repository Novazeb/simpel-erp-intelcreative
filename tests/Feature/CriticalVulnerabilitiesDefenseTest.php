<?php

namespace Tests\Feature;

use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CriticalVulnerabilitiesDefenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
    }

    /**
     * Umpan Balik 4 - Celah 1:
     * Penggunaan Soft Deletes pada tabel employees tidak berbenturan dengan constraint unik.
     * Mendaftarkan karyawan baru dengan NIK atau Email dari eks-karyawan yang telah di-soft-delete harus SUKSES.
     */
    public function test_soft_deleted_employee_allows_reusing_nik_and_email_without_query_exception(): void
    {
        $admin = User::where('email', 'admin@intelcreative.co.id')->first();
        $dept = Department::first();
        $desig = Designation::first();
        $shift = WorkShift::first();

        // 1. Buat karyawan pertama
        $emp1 = Employee::create([
            'nik' => 'ITC-TEST-001',
            'full_name' => 'Karyawan Lama (Nonaktif)',
            'email' => 'marketing.old@intelcreative.co.id',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'work_shift_id' => $shift->id,
            'employment_status' => 'PKWT',
            'join_date' => '2025-01-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '1122334455',
            'bank_account_holder' => 'Karyawan Lama',
            'basic_salary' => 6000000,
        ]);

        // 2. Soft delete karyawan pertama
        $emp1->delete();
        $this->assertSoftDeleted('employees', ['id' => $emp1->id]);

        // 3. Daftarkan karyawan baru dengan NIK dan Email yang sama via API / controller store
        $response = $this->actingAs($admin)->post('/hr/employees', [
            'nik' => 'ITC-TEST-001',
            'full_name' => 'Karyawan Baru Pengganti',
            'email' => 'marketing.old@intelcreative.co.id',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'work_shift_id' => $shift->id,
            'employment_status' => 'PKWTT',
            'join_date' => '2026-03-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '9988776655',
            'bank_account_holder' => 'Karyawan Baru',
            'basic_salary' => 7500000,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Karyawan baru berhasil tersimpan dan aktif
        $this->assertDatabaseHas('employees', [
            'email' => 'marketing.old@intelcreative.co.id',
            'full_name' => 'Karyawan Baru Pengganti',
            'deleted_at' => null,
        ]);

        // Total ada 2 baris (1 soft deleted, 1 active)
        $this->assertEquals(2, Employee::withTrashed()->where('email', 'marketing.old@intelcreative.co.id')->count());
    }

    /**
     * Umpan Balik 4 - Celah 1:
     * Karyawan aktif yang mencoba menggunakan NIK atau Email yang sudah aktif harus ditolak oleh validasi / unique index.
     */
    public function test_active_employees_cannot_have_duplicate_nik_or_email(): void
    {
        $admin = User::where('email', 'admin@intelcreative.co.id')->first();
        $dept = Department::first();
        $desig = Designation::first();
        $shift = WorkShift::first();

        // Ambil data staf yang aktif
        $activeEmp = Employee::whereNull('deleted_at')->first();

        // Percobaan mendaftarkan karyawan dengan email yang masih aktif harus gagal validasi
        $response = $this->actingAs($admin)->post('/hr/employees', [
            'nik' => 'ITC-UNIQUE-NEW',
            'full_name' => 'Duplicate Attempt',
            'email' => $activeEmp->email,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'work_shift_id' => $shift->id,
            'employment_status' => 'PKWT',
            'join_date' => '2026-04-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Duplicate',
            'basic_salary' => 5000000,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Umpan Balik 4 - Celah 2:
     * Celah Race Condition Clock-In dicegah menggunakan Cache Atomic Lock.
     * Permintaan simultan ketika lock sedang aktif harus ditolak dengan status HTTP 429 Too Many Requests.
     */
    public function test_clock_in_atomic_lock_prevents_simultaneous_race_conditions_with_429(): void
    {
        $staffUser = User::where('email', 'staff@intelcreative.co.id')->first();
        $staffEmployee = Employee::where('email', 'staff@intelcreative.co.id')->first();

        // 1. Kunci atomik pertama aktif (simulasi request sedang diproses)
        $lock = Cache::lock("clock_in_employee_{$staffEmployee->id}", 5);
        $this->assertTrue($lock->get());

        try {
            // 2. Request kedua masuk secara simultan ketika lock masih dipegang
            $response = $this->actingAs($staffUser)
                ->postJson('/attendance/clock-in', [
                    'timestamp' => now()->toDateTimeString(),
                ]);

            // Harus ditolak dengan status HTTP 429 Too Many Requests
            $response->assertStatus(429);
            $response->assertJson([
                'message' => 'Sistem sedang memproses presensi Anda, mohon tunggu.',
            ]);
        } finally {
            $lock->release();
        }

        // 3. Setelah lock dilepas, request berikutnya harus berhasil (200 OK)
        $successResponse = $this->actingAs($staffUser)
            ->postJson('/attendance/clock-in', [
                'timestamp' => now()->toDateTimeString(),
            ]);

        $successResponse->assertStatus(200);
        $successResponse->assertJson([
            'success' => true,
            'message' => 'Presensi masuk berhasil dicatat.',
        ]);
    }

    /**
     * Umpan Balik 4 - Celah 3:
     * Halaman kalkulator penawaran aman dengan presisi Big.js dapat diakses.
     */
    public function test_quotation_calculator_route_is_accessible(): void
    {
        $admin = User::where('email', 'admin@intelcreative.co.id')->first();

        $response = $this->actingAs($admin)->get('/operations/quotations');
        $response->assertStatus(200);
    }
}
