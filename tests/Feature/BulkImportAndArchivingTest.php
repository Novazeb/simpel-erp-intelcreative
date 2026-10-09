<?php

namespace Tests\Feature;

use App\Domains\HR\Services\EmployeeBulkImportService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BulkImportAndArchivingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
    }

    /**
     * 1. Uji Layanan Impor Massal Karyawan (Atomic & Validasi)
     */
    public function test_employee_bulk_import_service_success(): void
    {
        $csvContent = <<<'CSV'
nik,full_name,email,phone,department_code,designation_title,work_shift_name,employment_status,join_date,bank_name,bank_account_number,bank_account_holder,basic_salary,tax_status
ITC-IMP-001,Impor User Satu,impor1@intelcreative.co.id,081234567,TECH,Senior Dev,Reguler,PKWTT,2026-01-01,BCA,111222333,Impor Satu,15000000.00,TK/0
ITC-IMP-002,Impor User Dua,impor2@intelcreative.co.id,081234568,DESIGN,UI Designer,Reguler,PKWT,2026-02-01,Mandiri,444555666,Impor Dua,12000000.00,K/1
CSV;

        $service = new EmployeeBulkImportService;
        $rows = $service->parseCsv($csvContent);

        $this->assertCount(2, $rows);

        $count = $service->import($rows);
        $this->assertEquals(2, $count);

        $this->assertDatabaseHas('employees', ['nik' => 'ITC-IMP-001', 'email' => 'impor1@intelcreative.co.id']);
        $this->assertDatabaseHas('employees', ['nik' => 'ITC-IMP-002', 'email' => 'impor2@intelcreative.co.id']);
    }

    /**
     * 2. Uji Rollback Atomik Impor Jika Ada Baris Cacat / Duplikasi
     */
    public function test_employee_bulk_import_rollback_on_duplicate(): void
    {
        $csvContent = <<<'CSV'
nik,full_name,email,phone,department_code,designation_title,work_shift_name,employment_status,join_date,bank_name,bank_account_number,bank_account_holder,basic_salary,tax_status
ITC-GOOD,Valid User,valid@intelcreative.co.id,08111,TECH,Dev,Reguler,PKWT,2026-01-01,BCA,123,Valid,10000000.00,TK/0
ITC-GOOD,Duplikat User,dup@intelcreative.co.id,08112,TECH,Dev,Reguler,PKWT,2026-01-01,BCA,124,Dup,10000000.00,TK/0
CSV;

        $service = new EmployeeBulkImportService;
        $rows = $service->parseCsv($csvContent);

        $this->expectException(ValidationException::class);

        try {
            $service->import($rows);
        } finally {
            // Memastikan data baris pertama yang valid ikut dibatalkan (Rollback)
            $this->assertDatabaseMissing('employees', ['nik' => 'ITC-GOOD']);
        }
    }

    /**
     * 3. Uji Perintah Pengarsipan Jejak Audit (Cold Storage Archiving)
     */
    public function test_audit_trails_archiving_command(): void
    {
        // Masukkan data log audit berusia 14 bulan lalu
        DB::table('audit_trails')->insert([
            'id' => (string) Str::uuid(),
            'auditable_type' => 'App\Domains\HR\Models\Employee',
            'auditable_id' => (string) Str::uuid(),
            'event' => 'updated',
            'field_changed' => 'basic_salary',
            'old_value' => '10000000',
            'new_value' => '12000000',
            'created_at' => now()->subMonths(14),
        ]);

        // Masukkan data log audit baru (1 bulan lalu)
        DB::table('audit_trails')->insert([
            'id' => (string) Str::uuid(),
            'auditable_type' => 'App\Domains\HR\Models\Employee',
            'auditable_id' => (string) Str::uuid(),
            'event' => 'created',
            'field_changed' => null,
            'created_at' => now()->subMonth(),
        ]);

        $this->assertEquals(2, DB::table('audit_trails')->count());

        // Jalankan perintah arsip batas 12 bulan
        $exitCode = Artisan::call('audit:archive', ['--months' => 12]);
        $this->assertEquals(0, $exitCode);

        // Hanya tersisa 1 log baru di database, log lama telah dipindahkan ke cold storage
        $this->assertEquals(1, DB::table('audit_trails')->count());
    }
}
