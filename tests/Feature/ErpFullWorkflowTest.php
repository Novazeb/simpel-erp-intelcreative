<?php

namespace Tests\Feature;

use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\Attendance\Models\LeaveType;
use App\Domains\Attendance\Services\TardinessCalculator;
use App\Domains\Finance\Models\JournalEntry;
use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Domains\Payroll\Actions\ApprovePayrollPeriodAction;
use App\Domains\Payroll\Actions\CalculatePayrollBatchAction;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollSlip;
use App\Domains\Payroll\Services\PayrollCalculationEngine;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpFullWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
    }

    /**
     * TC-HR-01: Soft delete karyawan tidak menghapus riwayat slip gaji masa lalu.
     */
    public function test_tc_hr_01_soft_delete_preserves_payroll_slips(): void
    {
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering']);
        $des = Designation::create(['department_id' => $dept->id, 'title' => 'Software Engineer']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'late_tolerance_minutes' => 15]);

        $employee = Employee::create([
            'nik' => 'ITC-001',
            'full_name' => 'Budi Santoso',
            'email' => 'budi@intelcreative.co.id',
            'department_id' => $dept->id,
            'designation_id' => $des->id,
            'work_shift_id' => $shift->id,
            'employment_status' => 'PKWTT',
            'join_date' => '2026-01-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '123456',
            'bank_account_holder' => 'Budi Santoso',
            'basic_salary' => 10000000.00,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'cutoff_date' => '2026-09-30',
            'payment_date' => '2026-10-01',
            'status' => 'APPROVED',
        ]);

        $slip = PayrollSlip::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'basic_salary_snapshot' => 10000000.00,
            'take_home_pay' => 10000000.00,
        ]);

        // Act: Soft Delete
        $employee->delete();

        // Assert: Employee soft deleted, slip tetap utuh
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('payroll_slips', ['id' => $slip->id]);
        $this->assertEquals(10000000.00, (float) $slip->fresh()->basic_salary_snapshot);
    }

    /**
     * TC-ATT-01: Shift 09:00 (toleransi 15 mnt), 09:14 -> 0 mnt, 09:16 -> 16 mnt telat.
     */
    public function test_tc_att_01_tardiness_tolerance_calculation(): void
    {
        $calculator = new TardinessCalculator;

        // 09:14 (dalam toleransi 15 menit)
        $late1 = $calculator->calculateLateMinutes('09:00:00', 15, Carbon::parse('2026-10-08 09:14:00'));
        $this->assertEquals(0, $late1);

        // 09:16 (melebihi toleransi 15 menit, terhitung dari jam 09:00 = 16 menit)
        $late2 = $calculator->calculateLateMinutes('09:00:00', 15, Carbon::parse('2026-10-08 09:16:00'));
        $this->assertEquals(16, $late2);
    }

    /**
     * TC-ATT-02: 2 hari Unpaid Leave memicu potongan proporsional (2/hari_kerja) * gaji_pokok.
     */
    public function test_tc_att_02_unpaid_leave_deduction(): void
    {
        $dept = Department::create(['code' => 'MKT', 'name' => 'Marketing']);
        $des = Designation::create(['department_id' => $dept->id, 'title' => 'Marketer']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $employee = Employee::create([
            'nik' => 'ITC-002',
            'full_name' => 'Siti Rahma',
            'email' => 'siti@intelcreative.co.id',
            'department_id' => $dept->id,
            'designation_id' => $des->id,
            'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '987654',
            'bank_account_holder' => 'Siti Rahma',
            'basic_salary' => 10000000.00,
        ]);

        $leaveType = LeaveType::create([
            'code' => 'UNPAID',
            'name' => 'Unpaid Leave',
            'is_paid' => false,
            'default_quota_days' => 0,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll Oktober 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'cutoff_date' => '2026-10-31',
            'payment_date' => '2026-11-01',
            'status' => 'DRAFT',
        ]);

        // Cuti 2 hari unpaid
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'total_days' => 2,
            'reason' => 'Keperluan mendesak',
            'status' => 'APPROVED',
        ]);

        $engine = new PayrollCalculationEngine;
        $slip = $engine->calculateForEmployee($period, $employee);

        $unpaidItem = $slip->items()->where('name', 'Potongan Unpaid Leave')->first();
        $this->assertNotNull($unpaidItem);
        $this->assertTrue((float) $unpaidItem->amount > 0);
    }

    /**
     * TC-PAY-01 & TC-PAY-02: Snapshot basic_salary immutable & Idempotensi Hitung Ulang.
     */
    public function test_tc_pay_01_and_tc_pay_02_snapshot_and_idempotence(): void
    {
        $dept = Department::create(['code' => 'FIN', 'name' => 'Finance']);
        $des = Designation::create(['department_id' => $dept->id, 'title' => 'Finance Staff']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $employee = Employee::create([
            'nik' => 'ITC-003',
            'full_name' => 'Andi Wijaya',
            'email' => 'andi@intelcreative.co.id',
            'department_id' => $dept->id,
            'designation_id' => $des->id,
            'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '45678',
            'bank_account_holder' => 'Andi Wijaya',
            'basic_salary' => 10000000.00,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'cutoff_date' => '2026-09-30',
            'payment_date' => '2026-10-01',
            'status' => 'DRAFT',
        ]);

        $engine = new PayrollCalculationEngine;

        // Hitung Pertama
        $slip1 = $engine->calculateForEmployee($period, $employee);
        $this->assertEquals(10000000.00, (float) $slip1->basic_salary_snapshot);

        // Hitung Ulang Berkali-kali (TC-PAY-02: Idempotent - tidak ada duplikasi slip atau items)
        $slip2 = $engine->calculateForEmployee($period, $employee);
        $this->assertEquals(1, PayrollSlip::where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->count());

        // Karyawan dinaikkan gajinya jadi 12jt setelah approved (TC-PAY-01)
        $period->update(['status' => 'APPROVED']);
        $employee->update(['basic_salary' => 12000000.00]);

        $this->assertEquals(10000000.00, (float) $slip1->fresh()->basic_salary_snapshot);
    }

    /**
     * TC-FIN-01: Auto-Journal Keseimbangan Total Debit == Total Kredit (Balance = 0).
     */
    public function test_tc_fin_01_balanced_auto_journal(): void
    {
        $dept = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $des = Designation::create(['department_id' => $dept->id, 'title' => 'Operator']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $emp1 = Employee::create([
            'nik' => 'ITC-101', 'full_name' => 'Ahmad', 'email' => 'ahmad@intelcreative.co.id',
            'department_id' => $dept->id, 'designation_id' => $des->id, 'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01', 'bank_name' => 'BCA', 'bank_account_number' => '111',
            'bank_account_holder' => 'Ahmad', 'basic_salary' => 8000000.00,
        ]);

        $emp2 = Employee::create([
            'nik' => 'ITC-102', 'full_name' => 'Dewi', 'email' => 'dewi@intelcreative.co.id',
            'department_id' => $dept->id, 'designation_id' => $des->id, 'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01', 'bank_name' => 'BCA', 'bank_account_number' => '222',
            'bank_account_holder' => 'Dewi', 'basic_salary' => 12000000.00,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll Oktober 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'cutoff_date' => '2026-10-31',
            'payment_date' => '2026-11-01',
            'status' => 'DRAFT',
        ]);

        // Kalkulasi Batch
        $batchAction = app(CalculatePayrollBatchAction::class);
        $batchAction->execute($period);

        $this->assertEquals('CALCULATED', $period->fresh()->status);

        // Approval Direksi -> Terbitkan Auto-Journal
        $executiveUser = User::where('email', 'executive@intelcreative.co.id')->first();
        $approveAction = app(ApprovePayrollPeriodAction::class);
        $approveAction->execute($period->fresh(), $executiveUser);

        // Cek Journal Entry
        $journal = JournalEntry::where('reference_id', $period->id)->first();
        $this->assertNotNull($journal);

        $totalDebit = (float) $journal->items()->sum('debit');
        $totalCredit = (float) $journal->items()->sum('credit');

        $this->assertTrue($totalDebit > 0, 'Total debit harus lebih besar dari 0');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, 'Jurnal Payroll TIDAK SEIMBANG! Debit != Kredit');
    }

    /**
     * TC-SEC-01: Staf biasa mencoba akses periode payroll -> 403 Forbidden.
     */
    public function test_tc_sec_01_staff_forbidden_from_payroll_management(): void
    {
        $staffUser = User::where('email', 'staff@intelcreative.co.id')->first();

        $response = $this->actingAs($staffUser)->get('/payroll/periods');
        $response->assertStatus(403);
    }
}
