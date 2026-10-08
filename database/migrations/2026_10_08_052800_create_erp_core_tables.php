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
        // 1. HR MASTER DATA
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('title', 100);
            $table->timestamps();
        });

        Schema::create('work_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('late_tolerance_minutes')->default(0);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->unique();
            $table->string('nik', 30);
            $table->string('full_name', 150);
            $table->string('email', 100);
            $table->string('phone', 25)->nullable();
            $table->foreignUuid('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignUuid('designation_id')->constrained('designations')->restrictOnDelete();
            $table->foreignUuid('work_shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->string('employment_status', 20)->default('PKWT'); // PKWT, PKWTT, FREELANCE, PROBATION
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->string('bank_name', 50);
            $table->string('bank_account_number', 50);
            $table->string('bank_account_holder', 150);
            $table->decimal('basic_salary', 15, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('department_id');
        });

        // Partial Unique Indexes: Menjamin keunikan nik dan email hanya untuk karyawan aktif (deleted_at IS NULL).
        // Mencegah QueryException di PostgreSQL saat mendaftarkan karyawan dengan data eks-karyawan (umpanbalik4.md poin 1).
        DB::statement('CREATE UNIQUE INDEX employees_email_unique ON employees (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX employees_nik_unique ON employees (nik) WHERE deleted_at IS NULL');

        // 2. ATTENDANCE & LEAVE
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('work_date');
            $table->timestampTz('clock_in')->nullable();
            $table->timestampTz('clock_out')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->decimal('overtime_hours', 4, 2)->default(0.00);
            $table->string('status', 20)->default('PRESENT'); // PRESENT, LATE, ABSENT, ON_LEAVE, HOLIDAY
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'uq_employee_work_date');
            $table->index('work_date');
            $table->index(['employee_id', 'work_date']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->boolean('is_paid')->default(true);
            $table->integer('default_quota_days')->default(12);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUuid('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED, CANCELLED
            $table->uuid('approved_by')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestamps();
        });

        // 3. PAYROLL
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('cutoff_date');
            $table->date('payment_date');
            $table->string('status', 20)->default('DRAFT'); // DRAFT, CALCULATING, CALCULATED, APPROVED, PAID, CANCELLED
            $table->uuid('approved_by')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('basic_salary_snapshot', 15, 2);
            $table->decimal('total_allowances', 15, 2)->default(0.00);
            $table->decimal('total_deductions', 15, 2)->default(0.00);
            $table->decimal('take_home_pay', 15, 2)->default(0.00);
            $table->integer('attendance_count')->default(0);
            $table->integer('absent_count')->default(0);
            $table->integer('late_minutes_count')->default(0);
            $table->decimal('overtime_hours_count', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id'], 'uq_slip_period_employee');
            $table->index('payroll_period_id');
        });

        Schema::create('payroll_slip_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_slip_id')->constrained('payroll_slips')->cascadeOnDelete();
            $table->string('item_type', 20); // ALLOWANCE, DEDUCTION, OVERTIME, TAX, BENEFIT
            $table->string('name', 100);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        // 4. FINANCE & ACCOUNTING
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('type', 20); // ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE
            $table->timestamps();
        });

        Schema::create('disbursement_vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('voucher_number', 50)->unique();
            $table->foreignUuid('payroll_period_id')->unique()->constrained('payroll_periods')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            $table->string('status', 20)->default('DRAFT'); // DRAFT, RELEASED, RECONCILED
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entry_number', 50)->unique();
            $table->string('reference_type', 50); // PAYROLL_DISBURSEMENT
            $table->uuid('reference_id');
            $table->date('transaction_date');
            $table->text('description');
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('journal_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index('journal_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('disbursement_vouchers');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('payroll_slip_items');
        Schema::dropIfExists('payroll_slips');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('work_shifts');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
    }
};
