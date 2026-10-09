<?php

namespace Tests\Feature;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\Finance\Models\Reimbursement;
use App\Domains\HR\Models\Employee;
use App\Domains\Procurement\Models\AssetItem;
use App\Domains\Procurement\Services\AssetDepreciationService;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Timesheet;
use App\Domains\Project\Services\ProjectProfitabilityService;
use App\Domains\Security\Models\Delegation;
use App\Domains\Security\Models\InternalDocument;
use App\Domains\Security\Services\DelegationService;
use App\Domains\Shared\Services\DigitalSignatureService;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\GoldFeaturesSeeder;
use Database\Seeders\KioskScannerRoleSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StaffSimulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GoldFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $rianUser;

    protected Employee $rianEmployee;

    protected User $financeUser;

    protected Employee $dewiEmployee;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
        $this->seed(StaffSimulationSeeder::class);
        $this->seed(KioskScannerRoleSeeder::class);
        $this->seed(GoldFeaturesSeeder::class);

        $this->rianUser = User::where('email', 'rian.ardiansyah@intelcreative.co.id')->first();
        $this->rianEmployee = Employee::where('email', 'rian.ardiansyah@intelcreative.co.id')->first();
        $this->dewiEmployee = Employee::where('email', 'dewi.safitri@intelcreative.co.id')->first();
        $this->financeUser = User::where('email', 'finance@intelcreative.co.id')->first();
        $this->project = Project::where('project_code', 'PRJ-2026-001')->first();
    }

    /**
     * TC-GOLD-01: Input Timesheet Dinamis
     */
    public function test_tc_gold_01_dynamic_timesheet_creation(): void
    {
        $response = $this->actingAs($this->rianUser)->postJson('/operations/timesheets', [
            'project_id' => $this->project->id,
            'work_date' => '2026-10-09',
            'billable_hours' => 7.5,
            'task_description' => 'Eksplorasi Design Token dan Komponen UI Vue',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('timesheets', [
            'employee_id' => $this->rianEmployee->id,
            'project_id' => $this->project->id,
            'billable_hours' => '7.50',
            'status' => 'APPROVED',
        ]);
    }

    /**
     * TC-GOLD-02: Widget QR Code 30s
     */
    public function test_tc_gold_02_dynamic_qr_svg_generation(): void
    {
        $response = $this->actingAs($this->rianUser)->getJson('/api/v1/attendance/my-qr');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'nik',
            'full_name',
            'timestamp',
            'qr_payload',
            'qr_svg',
            'ttl_seconds',
            'expires_in_seconds',
        ]);

        $data = $response->json();
        $this->assertLessThanOrEqual(30, $data['ttl_seconds']);
        $this->assertStringContainsString('<svg', $data['qr_svg']);
        $this->assertStringContainsString('</svg>', $data['qr_svg']);
    }

    /**
     * TC-GOLD-03: Onboarding Tengah Hari (Scheduler Skip)
     */
    public function test_tc_gold_03_midday_onboarding_not_marked_absent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:26:00', 'Asia/Jakarta'));

        // Buat karyawan baru yang bergabung di hari H (2026-10-09)
        $newEmployee = Employee::create([
            'nik' => 'ITC-NEW-999',
            'full_name' => 'Budi Baru Masuk',
            'email' => 'budi.baru@intelcreative.co.id',
            'department_id' => $this->rianEmployee->department_id,
            'designation_id' => $this->rianEmployee->designation_id,
            'work_shift_id' => $this->rianEmployee->work_shift_id,
            'employment_status' => 'PKWT',
            'join_date' => '2026-10-09',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Baru Masuk',
            'basic_salary' => 8000000.00,
        ]);

        // Eksekusi penutupan window presensi
        $exitCode = Artisan::call('attendance:close-daily-window');
        $this->assertEquals(0, $exitCode);

        // Karyawan baru yang bergabung hari ini TIDAK boleh dicatat ABSENT
        $absentRecord = Attendance::where('employee_id', $newEmployee->id)
            ->whereDate('work_date', '2026-10-09')
            ->first();

        $this->assertNull($absentRecord);
    }

    /**
     * TC-GOLD-04: Direct Labor Cost & Project Profitability
     */
    public function test_tc_gold_04_direct_labor_cost_calculation(): void
    {
        $service = new ProjectProfitabilityService;
        $metrics = $service->calculateProfitability($this->project->id);

        $this->assertEquals($this->project->id, $metrics['project_id']);
        $this->assertEquals(180000000.00, $metrics['contract_budget']);
        $this->assertGreaterThan(0, $metrics['direct_labor_cost']);
        $this->assertGreaterThan(0, $metrics['gross_profit']);
        $this->assertEquals('HEALTHY', $metrics['health_status']);
    }

    /**
     * TC-GOLD-05: e-Signature SHA-256
     */
    public function test_tc_gold_05_digital_signature_stamp_generation(): void
    {
        $service = new DigitalSignatureService;
        $stamp = $service->generateSignatureStamp($this->rianEmployee, 'PAYROLL-SLIP-2026-09');

        $this->assertEquals($this->rianEmployee->full_name, $stamp['signer_name']);
        $this->assertNotEmpty($stamp['document_hash']);
        $this->assertEquals(64, strlen($stamp['document_hash'])); // SHA-256 hex length
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $stamp['qr_code_base64']);
    }

    /**
     * TC-GOLD-06: Depresiasi Garis Lurus & Auto-Journal
     */
    public function test_tc_gold_06_asset_depreciation_and_auto_journal(): void
    {
        $service = new AssetDepreciationService;
        $processed = $service->processMonthlyDepreciation();

        $this->assertGreaterThan(0, $processed);

        $asset = AssetItem::where('asset_code', 'AST-IT-2026-001')->first();
        $this->assertLessThan(36000000.00, (float) $asset->current_book_value);

        // Pastikan Auto-Journal Beban Penyusutan terbentuk
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'ASSET_DEPRECIATION',
            'reference_id' => $asset->id,
        ]);
    }

    /**
     * TC-GOLD-07: SaaS Expiration Alert (7 Hari)
     */
    public function test_tc_gold_07_saas_renewal_alert_command(): void
    {
        // Subskripsi Adobe CC jatuh tempo 5 hari dari sekarang (GoldFeaturesSeeder)
        $exitCode = Artisan::call('saas:check-renewals');
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Adobe Creative Cloud Enterprise', $output);
        $this->assertStringContainsString('PERINGATAN SAAS', $output);
    }

    /**
     * TC-GOLD-08: Reimbursement Petty Cash & Auto-Journal
     */
    public function test_tc_gold_08_reimbursement_petty_cash_auto_journal(): void
    {
        // 1. Submit Reimbursement oleh Rian
        $storeResponse = $this->actingAs($this->rianUser)->postJson('/finance/reimbursements', [
            'title' => 'Penggantian Biaya Transport Klien & Konsumsi',
            'claim_date' => '2026-10-09',
            'project_id' => $this->project->id,
            'items' => [
                [
                    'category' => 'TRANSPORT',
                    'description' => 'Taksi Kunjungan Meeting Klien',
                    'amount' => 150000,
                ],
                [
                    'category' => 'MEALS',
                    'description' => 'Makan Siang Tim Desain',
                    'amount' => 100000,
                ],
            ],
        ]);

        $storeResponse->assertStatus(201);
        $reimbId = $storeResponse->json('data.id');

        // 2. Disetujui oleh Finance
        $approveResponse = $this->actingAs($this->financeUser)->postJson("/finance/reimbursements/{$reimbId}/approve");
        $approveResponse->assertStatus(200);

        $reimbursement = Reimbursement::find($reimbId);
        $this->assertEquals('PAID', $reimbursement->status);
        $this->assertEquals(250000.00, (float) $reimbursement->total_amount);

        // Pastikan Auto-Journal Kas Kecil terbentuk
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'REIMBURSEMENT',
            'reference_id' => $reimbursement->id,
        ]);
    }

    /**
     * TC-GOLD-09: Delegasi Acting Manager
     */
    public function test_tc_gold_09_acting_manager_delegation(): void
    {
        // Rian mendelegasikan wewenang approval ke Dewi selama 3 hari
        Delegation::create([
            'delegator_id' => $this->rianEmployee->id,
            'delegatee_id' => $this->dewiEmployee->id,
            'start_date' => '2026-10-08',
            'end_date' => '2026-10-12',
            'scope' => 'ALL_APPROVALS',
            'reason' => 'Cuti Tahunan',
            'is_active' => true,
        ]);

        $service = new DelegationService;
        $this->assertTrue($service->canActAs($this->dewiEmployee, $this->rianEmployee, 'ALL_APPROVALS', '2026-10-09'));
        $this->assertFalse($service->canActAs($this->dewiEmployee, $this->rianEmployee, 'ALL_APPROVALS', '2026-10-15')); // Di luar rentang
    }

    /**
     * TC-GOLD-10: Document Read Acknowledgment
     */
    public function test_tc_gold_10_document_read_acknowledgment(): void
    {
        $doc = InternalDocument::where('document_number', 'SOP-HR-2026-001')->first();

        $response = $this->actingAs($this->rianUser)->postJson("/documents/{$doc->id}/acknowledge");
        $response->assertStatus(200);

        $this->assertDatabaseHas('document_acknowledgments', [
            'internal_document_id' => $doc->id,
            'employee_id' => $this->rianEmployee->id,
        ]);
    }
}
