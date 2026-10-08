<?php

namespace Tests\Feature;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\HR\Models\Employee;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessAndAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
    }

    /**
     * Uji 1: Login sebagai staff@intelcreative.co.id dialihkan ke portal presensi mandiri (/attendance) dan tidak menghasilkan 403.
     */
    public function test_staff_login_redirects_to_attendance_without_403(): void
    {
        $response = $this->post('/login', [
            'email' => 'staff@intelcreative.co.id',
            'password' => 'SecretPassword123!',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertAuthenticated();

        // Mengikuti redirect ke /attendance harus berstatus 200 OK (Bukan 403 Forbidden)
        $attendanceResponse = $this->get('/attendance');
        $attendanceResponse->assertStatus(200);
    }

    /**
     * Uji 2: Staff dapat mengakses halaman presensi mandiri dan hanya melihat presensi miliknya.
     */
    public function test_staff_can_view_own_attendance_only(): void
    {
        $staffUser = User::where('email', 'staff@intelcreative.co.id')->first();
        $staffEmployee = Employee::where('email', 'staff@intelcreative.co.id')->first();

        // Presensi staf
        Attendance::create([
            'employee_id' => $staffEmployee->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(8, 55),
            'late_minutes' => 0,
            'status' => 'PRESENT',
        ]);

        $response = $this->actingAs($staffUser)->get('/attendance');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/Timesheet')
            ->has('attendances.data', 1)
        );
    }

    /**
     * Uji 3: Staff dapat mencatat presensi (clock-in) mandiri secara sukses.
     */
    public function test_staff_can_clock_in_successfully(): void
    {
        $staffUser = User::where('email', 'staff@intelcreative.co.id')->first();

        $response = $this->actingAs($staffUser)->postJson('/attendance/clock-in', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Presensi masuk berhasil dicatat.',
        ]);

        $staffEmployee = Employee::where('email', 'staff@intelcreative.co.id')->first();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $staffEmployee->id,
            'status' => 'PRESENT',
        ]);
    }

    /**
     * Uji 4: Staff diblokir (403 Forbidden) ketika mencoba mengakses modul manajerial HR atau Payroll.
     */
    public function test_staff_is_forbidden_from_managerial_routes(): void
    {
        $staffUser = User::where('email', 'staff@intelcreative.co.id')->first();

        // Tidak boleh akses direktori karyawan HR
        $hrResponse = $this->actingAs($staffUser)->get('/hr/employees');
        $hrResponse->assertStatus(403);

        // Tidak boleh akses periode payroll
        $payrollResponse = $this->actingAs($staffUser)->get('/payroll/periods');
        $payrollResponse->assertStatus(403);

        // Tidak boleh akses voucher keuangan
        $financeResponse = $this->actingAs($staffUser)->get('/finance/vouchers');
        $financeResponse->assertStatus(403);
    }

    /**
     * Uji 5: Admin / Manajemen login dialihkan ke /dashboard dan dapat mengakses /hr/employees.
     */
    public function test_admin_login_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@intelcreative.co.id',
            'password' => 'SecretPassword123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $adminUser = User::where('email', 'admin@intelcreative.co.id')->first();
        $hrResponse = $this->actingAs($adminUser)->get('/hr/employees');
        $hrResponse->assertStatus(200);
    }

    /**
     * Uji 6: Rian Ardiansyah dan Dewi Safitri dapat login, mengakses /payroll/my-slips dan melihat snapshot gaji.
     */
    public function test_simulation_staff_rian_and_dewi_can_access_my_slips(): void
    {
        $this->seed(\Database\Seeders\StaffSimulationSeeder::class);

        // Uji Rian Ardiansyah
        $rianUser = User::where('email', 'rian.ardiansyah@intelcreative.co.id')->first();
        $rianResponse = $this->actingAs($rianUser)->get('/payroll/my-slips');
        $rianResponse->assertStatus(200);
        $rianResponse->assertInertia(fn ($page) => $page
            ->component('Payroll/MySalarySnapshot')
            ->has('employee')
            ->has('slips')
            ->where('employee.full_name', 'Rian Ardiansyah')
        );

        // Uji Dewi Safitri
        $dewiUser = User::where('email', 'dewi.safitri@intelcreative.co.id')->first();
        $dewiResponse = $this->actingAs($dewiUser)->get('/payroll/my-slips');
        $dewiResponse->assertStatus(200);
        $dewiResponse->assertInertia(fn ($page) => $page
            ->component('Payroll/MySalarySnapshot')
            ->has('employee')
            ->has('slips')
            ->where('employee.full_name', 'Dewi Safitri')
        );
    }

    /**
     * Uji 7: Rian Ardiansyah dan Dewi Safitri dapat mengakses halaman Proyek & Timesheet penugasan mereka.
     */
    public function test_simulation_staff_can_view_projects_and_timesheets(): void
    {
        $this->seed(\Database\Seeders\StaffSimulationSeeder::class);

        $rianUser = User::where('email', 'rian.ardiansyah@intelcreative.co.id')->first();
        
        $projectResponse = $this->actingAs($rianUser)->get('/operations/projects');
        $projectResponse->assertStatus(200);
        $projectResponse->assertInertia(fn ($page) => $page
            ->component('Operations/ProjectList')
            ->has('projects')
            ->has('my_assignments')
        );

        $timesheetResponse = $this->actingAs($rianUser)->get('/operations/timesheets');
        $timesheetResponse->assertStatus(200);
        $timesheetResponse->assertInertia(fn ($page) => $page
            ->component('Operations/TimesheetList')
            ->has('timesheets')
        );
    }
}
