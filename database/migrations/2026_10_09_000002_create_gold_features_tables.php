<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0. TABEL MASTER KLIEN, PROYEK, & TUGAS (Mendukung Modul Operasional & Profitabilitas)
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('company_name', 150);
                $table->string('email', 100)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('project_code', 50)->unique();
                $table->string('project_name', 150);
                $table->foreignUuid('client_id')->nullable()->constrained('clients')->onDelete('set null');
                $table->decimal('budget', 15, 2)->default(0.00);
                $table->string('status', 30)->default('IN_PROGRESS'); // IN_PROGRESS, COMPLETED, ON_HOLD
                $table->date('deadline')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('projects')->onDelete('cascade');
                $table->string('name', 150);
                $table->string('status', 30)->default('TODO'); // TODO, IN_PROGRESS, DONE
                $table->timestamps();
            });
        }

        // 1. TABEL TIMESHEETS (Penyelesaian Masalah 1 Review 6)
        Schema::create('timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->date('work_date');
            $table->decimal('billable_hours', 4, 2)->default(0.00);
            $table->text('task_description');
            $table->string('status', 20)->default('SUBMITTED'); // SUBMITTED, APPROVED, REJECTED
            $table->foreignUuid('approved_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
            $table->index(['project_id', 'status']);
        });

        // 2. MODUL ASET & INVENTARIS IT
        Schema::create('asset_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('asset_code', 50)->unique(); // e.g. AST-IT-2026-001
            $table->string('name', 150);
            $table->string('category', 50); // HARDWARE, SOFTWARE, OFFICE_EQUIPMENT
            $table->string('serial_number', 100)->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->integer('useful_life_years')->default(4); // Masa manfaat (tahun)
            $table->decimal('salvage_value', 15, 2)->default(0.00); // Nilai sisa
            $table->decimal('current_book_value', 15, 2);
            $table->string('status', 30)->default('AVAILABLE'); // AVAILABLE, IN_USE, MAINTENANCE, DISPOSED
            $table->foreignUuid('assigned_employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_borrowings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('borrowed_at');
            $table->date('expected_return_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('condition_before', 100)->default('GOOD');
            $table->string('condition_after', 100)->nullable();
            $table->foreignUuid('processed_by')->constrained('employees')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->date('depreciation_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->foreignUuid('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->timestamps();
        });

        // 3. TRACKER LISENSI SOFTWARE & SAAS
        Schema::create('saas_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('software_name', 100); // Adobe CC, Figma, Midjourney, AWS
            $table->string('vendor_name', 100);
            $table->string('billing_cycle', 20)->default('MONTHLY'); // MONTHLY, YEARLY
            $table->decimal('cost_per_cycle', 15, 2);
            $table->integer('total_seats')->default(1);
            $table->date('start_date');
            $table->date('next_billing_date');
            $table->foreignUuid('assigned_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->string('payment_method', 50)->default('CORPORATE_CREDIT_CARD');
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, CANCELLED, EXPIRING_SOON
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();
        });

        // 4. KLAIM BIAYA & KAS KECIL (REIMBURSEMENT)
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('claim_number', 50)->unique(); // REIMB-202610-001
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->date('claim_date');
            $table->string('title', 150);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('status', 30)->default('SUBMITTED'); // SUBMITTED, APPROVED_SUPERVISOR, APPROVED_FINANCE, PAID, REJECTED
            $table->foreignUuid('approved_by_supervisor')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignUuid('approved_by_finance')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignUuid('disbursement_voucher_id')->nullable()->constrained('disbursement_vouchers')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('reimbursement_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reimbursement_id')->constrained('reimbursements')->onDelete('cascade');
            $table->date('expense_date');
            $table->string('category', 50); // TRANSPORT, MEALS, CLIENT_ENTERTAINMENT, SUPPLIES
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->string('receipt_path', 255)->nullable();
            $table->timestamps();
        });

        // 5. DELEGASI WEWENANG SEMENTARA (DELEGATION OF AUTHORITY)
        Schema::create('delegations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delegator_id')->constrained('employees')->onDelete('cascade'); // Pejabat Asli
            $table->foreignUuid('delegatee_id')->constrained('employees')->onDelete('cascade'); // Acting Manager
            $table->date('start_date');
            $table->date('end_date');
            $table->string('scope', 50)->default('ALL_APPROVALS'); // LEAVE_ONLY, REIMBURSEMENT_ONLY, ALL_APPROVALS
            $table->text('reason');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. PUSAT MANAJEMEN DOKUMEN & SOP
        Schema::create('internal_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_number', 50)->unique(); // SOP-HR-2026-001
            $table->string('title', 200);
            $table->string('category', 50); // SOP, COMPANY_POLICY, CONTRACT_TEMPLATE, TECHNICAL_GUIDE
            $table->string('version', 20)->default('1.0');
            $table->string('file_path', 255);
            $table->boolean('requires_acknowledgment')->default(false);
            $table->foreignUuid('uploaded_by')->constrained('employees')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('document_acknowledgments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('internal_document_id')->constrained('internal_documents')->onDelete('cascade');
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->timestamp('acknowledged_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['internal_document_id', 'employee_id']);
        });

        // 7. PENEMPELAN E-SIGNATURE PADA EMPLOYEES
        if (! Schema::hasColumn('employees', 'signature_specimen_path')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('signature_specimen_path')->nullable()->after('kiosk_pin_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'signature_specimen_path')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('signature_specimen_path');
            });
        }

        Schema::dropIfExists('document_acknowledgments');
        Schema::dropIfExists('internal_documents');
        Schema::dropIfExists('delegations');
        Schema::dropIfExists('reimbursement_items');
        Schema::dropIfExists('reimbursements');
        Schema::dropIfExists('saas_subscriptions');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('asset_borrowings');
        Schema::dropIfExists('asset_items');
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
    }
};
