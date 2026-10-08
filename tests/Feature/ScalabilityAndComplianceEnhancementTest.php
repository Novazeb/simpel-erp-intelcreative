<?php

namespace Tests\Feature;

use App\Domains\Attendance\Actions\ProcessMultiLevelLeaveApprovalAction;
use App\Domains\Attendance\Models\LeaveApprovalStep;
use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\Attendance\Models\LeaveType;
use App\Domains\Finance\Models\DisbursementVoucher;
use App\Domains\Finance\Services\CorporateBankingExporter;
use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Domains\Payroll\Actions\CalculatePayrollBatchAction;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollSlip;
use App\Domains\Payroll\Services\BpjsCapCalculator;
use App\Domains\Payroll\Services\Pph21TerCalculator;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScalabilityAndComplianceEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
    }

    /**
     * 1. Multi-Level Approval Test (Supervisor -> HOD -> HR)
     */
    public function test_multi_level_leave_approval_workflow(): void
    {
        $dept = Department::create(['code' => 'TECH', 'name' => 'Technology']);
        $desStaff = Designation::create(['department_id' => $dept->id, 'title' => 'Junior Dev']);
        $desLead = Designation::create(['department_id' => $dept->id, 'title' => 'Team Lead']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $supervisor = Employee::create([
            'nik' => 'ITC-LEAD', 'full_name' => 'Lead Developer', 'email' => 'lead@intelcreative.co.id',
            'department_id' => $dept->id, 'designation_id' => $desLead->id, 'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01', 'bank_name' => 'BCA', 'bank_account_number' => '999', 'bank_account_holder' => 'Lead',
            'basic_salary' => 15000000.00,
        ]);

        $staff = Employee::create([
            'nik' => 'ITC-STAFF', 'full_name' => 'Junior Staff', 'email' => 'junior@intelcreative.co.id',
            'department_id' => $dept->id, 'designation_id' => $desStaff->id, 'work_shift_id' => $shift->id,
            'manager_id' => $supervisor->id,
            'join_date' => '2026-01-01', 'bank_name' => 'BCA', 'bank_account_number' => '888', 'bank_account_holder' => 'Staff',
            'basic_salary' => 8000000.00,
        ]);

        $leaveType = LeaveType::create(['code' => 'ANNUAL', 'name' => 'Annual Leave', 'is_paid' => true, 'default_quota_days' => 12]);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $staff->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'status' => 'PENDING',
        ]);

        // Inisialisasi 2 step (Step 1: Supervisor, Step 2: HOD/HR)
        LeaveApprovalStep::create(['leave_request_id' => $leaveRequest->id, 'approver_id' => $supervisor->id, 'step_level' => 1, 'status' => 'PENDING']);
        LeaveApprovalStep::create(['leave_request_id' => $leaveRequest->id, 'approver_id' => $supervisor->id, 'step_level' => 2, 'status' => 'PENDING']);

        $action = new ProcessMultiLevelLeaveApprovalAction();

        // Step 1: Supervisor Approve
        $action->approve($leaveRequest, $supervisor, 'Disetujui lead');
        $this->assertEquals('PENDING_HOD', $leaveRequest->fresh()->status);

        // Step 2: Final Approve
        $action->approve($leaveRequest->fresh(), $supervisor, 'Disetujui final');
        $this->assertEquals('APPROVED', $leaveRequest->fresh()->status);
    }

    /**
     * 2. PPh 21 TER (PP 58/2023 & PMK 168/2023) & BPJS Cap Calculation
     */
    public function test_pph21_ter_and_bpjs_salary_cap(): void
    {
        $pph = new Pph21TerCalculator();
        // Di bawah PTKP bulanan dasar (<= 5.400.000) -> 0%
        $this->assertEquals(0.00, $pph->calculateMonthlyTax(5000000.00, 'TK/0'));

        // Gaji 10.000.000 (Kategori A: TER 2%) -> 200.000
        $this->assertEquals(200000.00, $pph->calculateMonthlyTax(10000000.00, 'TK/0'));

        $bpjs = new BpjsCapCalculator();
        // BPJS Kes: Capped pada Rp 12.000.000 (Gaji 20jt tetap kena 1% dari 12jt = 120.000)
        $this->assertEquals(120000.00, $bpjs->calculateBpjsKesehatan(20000000.00));

        // BPJS TK JP: Capped pada Rp 10.042.300
        $this->assertEquals(100423.00, round(min(20000000.00, BpjsCapCalculator::BPJS_TK_JP_MAX_BASE) * 0.01, 2));
    }

    /**
     * 3. Corporate Banking Exporter (KlikBCA & Mandiri MCM)
     */
    public function test_corporate_banking_exporter_generates_valid_files(): void
    {
        $dept = Department::create(['code' => 'OPS', 'name' => 'Ops']);
        $des = Designation::create(['department_id' => $dept->id, 'title' => 'Staff']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $emp = Employee::create([
            'nik' => 'ITC-BANK', 'full_name' => 'Ahmad Suhendra', 'email' => 'ahmad@intelcreative.co.id',
            'department_id' => $dept->id, 'designation_id' => $des->id, 'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01', 'bank_name' => 'BCA', 'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Ahmad Suhendra', 'basic_salary' => 10000000.00,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll Oktober 2026', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
            'cutoff_date' => '2026-10-31', 'payment_date' => '2026-11-01', 'status' => 'APPROVED',
        ]);

        PayrollSlip::create([
            'payroll_period_id' => $period->id, 'employee_id' => $emp->id,
            'basic_salary_snapshot' => 10000000.00, 'take_home_pay' => 9500000.00,
        ]);

        $voucher = DisbursementVoucher::create([
            'voucher_number' => 'DISB-TEST-001', 'payroll_period_id' => $period->id,
            'payment_date' => '2026-11-01', 'total_amount' => 9500000.00, 'status' => 'RELEASED',
        ]);

        $exporter = new CorporateBankingExporter();

        // BCA
        $bcaContent = $exporter->exportBcaFormat($voucher);
        $this->assertStringContainsString('H,20261101,1,9500000', $bcaContent);
        $this->assertStringContainsString('D,1234567890,9500000,AHMAD SUHENDRA', $bcaContent);

        // Mandiri
        $mandiriContent = $exporter->exportMandiriMcmFormat($voucher);
        $this->assertStringContainsString('"1234567890","Ahmad Suhendra","BCA",9500000.00,IDR', $mandiriContent);
    }

    /**
     * 4. Batch Job Chunking Execution Test
     */
    public function test_batch_chunking_payroll_calculation(): void
    {
        $dept = Department::create(['code' => 'IT', 'name' => 'IT']);
        $des = Designation::create(['department_id' => $dept->id, 'title' => 'Dev']);
        $shift = WorkShift::create(['name' => 'Reguler', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        Employee::create([
            'nik' => 'ITC-CHUNK', 'full_name' => 'Bambang', 'email' => 'bambang@intelcreative.co.id',
            'department_id' => $dept->id, 'designation_id' => $des->id, 'work_shift_id' => $shift->id,
            'join_date' => '2026-01-01', 'bank_name' => 'BCA', 'bank_account_number' => '777',
            'bank_account_holder' => 'Bambang', 'basic_salary' => 9000000.00,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll Batch Test', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
            'cutoff_date' => '2026-10-31', 'payment_date' => '2026-11-01', 'status' => 'DRAFT',
        ]);

        $batchAction = app(CalculatePayrollBatchAction::class);
        $batchAction->execute($period, true); // true for synchronous execution

        $this->assertEquals('CALCULATED', $period->fresh()->status);
        $this->assertDatabaseHas('payroll_slips', ['payroll_period_id' => $period->id]);
    }
}

