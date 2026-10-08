<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifikasi tabel employees (Hierarki manajer, status pajak PTKP TER, nomor BPJS)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignUuid('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('tax_status', 10)->default('TK/0'); // TK/0, TK/1, K/0, K/1, etc.
            $table->string('bpjs_tk_number', 50)->nullable();
            $table->string('bpjs_kes_number', 50)->nullable();

            $table->index('manager_id');
        });

        // Partial Unique Indexes: Menjamin keunikan nik dan email hanya untuk karyawan aktif (deleted_at IS NULL).
        // Mencegah QueryException di PostgreSQL saat mendaftarkan karyawan dengan data eks-karyawan (umpanbalik4.md poin 1).
        DB::statement('DROP INDEX IF EXISTS employees_email_unique');
        DB::statement('DROP INDEX IF EXISTS employees_nik_unique');
        DB::statement('CREATE UNIQUE INDEX employees_email_unique ON employees (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX employees_nik_unique ON employees (nik) WHERE deleted_at IS NULL');

        // 2. Modifikasi tabel leave_requests status check & Multi-Level Approval Steps
        Schema::create('leave_approval_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignUuid('approver_id')->constrained('employees')->restrictOnDelete();
            $table->integer('step_level'); // 1: Supervisor, 2: HOD, 3: HR
            $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->text('note')->nullable();
            $table->timestampTz('action_at')->nullable();
            $table->timestamps();

            $table->index(['leave_request_id', 'step_level']);
        });

        // 3. Tabel Riwayat Audit Trail Temporal (Kompensasi & Perubahan Sensitif)
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type');
            $table->uuid('auditable_id');
            $table->string('event'); // created, updated, deleted
            $table->string('field_changed')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
        Schema::dropIfExists('leave_approval_steps');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['manager_id', 'tax_status', 'bpjs_tk_number', 'bpjs_kes_number']);
        });
    }
};
