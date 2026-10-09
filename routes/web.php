<?php

use App\Http\Controllers\Admin\GovernanceController;
use App\Http\Controllers\Api\AttendanceQrController;
use App\Http\Controllers\Approval\ApprovalCenterController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\GeneralLedgerController;
use App\Http\Controllers\Finance\ReimbursementController;
use App\Http\Controllers\Finance\VoucherController;
use App\Http\Controllers\Governance\DocumentController;
use App\Http\Controllers\Health\HealthCheckController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\Operations\ProjectController;
use App\Http\Controllers\Operations\TimesheetController;
use App\Http\Controllers\Payroll\PayrollController;
use App\Http\Controllers\Procurement\AssetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Production Health Check & Monitoring API
Route::get('/api/health', [HealthCheckController::class, 'check'])->name('api.health');

// Terminal Kios Tablet Lobby & Dynamic QR Attendance API
Route::get('/kiosk', [AttendanceQrController::class, 'showKiosk'])->name('kiosk.scanner');
Route::post('/api/v1/attendance/qr-scan', [AttendanceQrController::class, 'processScan'])->name('api.attendance.qr-scan');
Route::get('/api/v1/attendance/my-qr', [AttendanceQrController::class, 'generateMyQr'])->middleware(['auth'])->name('api.attendance.my-qr');

Route::middleware(['auth'])->group(function () {
    // 0. Ringkasan Eksekutif
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/approvals', [ApprovalCenterController::class, 'index'])->name('approvals.index');

    // 1. HR & Employee Master Data
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])
            ->middleware('permission:employee.view-any')
            ->name('employees.index');

        Route::post('/employees', [EmployeeController::class, 'store'])
            ->middleware('permission:employee.create')
            ->name('employees.store');

        Route::post('/employees/import', [EmployeeController::class, 'importCsv'])
            ->middleware('permission:employee.create')
            ->name('employees.import');

        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:employee.update')
            ->name('employees.destroy');
    });

    // 2. Attendance & Leave
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])
            ->middleware('permission:attendance.view-all|attendance.record-self')
            ->name('index');

        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])
            ->middleware('permission:attendance.record-self')
            ->name('clock-in');

        Route::get('/my-qr', [AttendanceQrController::class, 'generateMyQr'])
            ->middleware('permission:attendance.record-self')
            ->name('my-qr');
    });

    // 3. Operasional & Bisnis Kreatif
    Route::prefix('operations')->name('operations.')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/timesheets', [TimesheetController::class, 'index'])->name('timesheets.index');
        Route::post('/timesheets', [TimesheetController::class, 'store'])->name('timesheets.store');
        Route::get('/quotations', fn () => Inertia::render('Operations/QuotationForm'))->name('quotations.index');
    });

    // 4. Pengadaan & Aset
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    });

    // 5. Payroll Engine
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/periods', [PayrollController::class, 'index'])
            ->middleware('permission:payroll.period-manage')
            ->name('periods.index');

        Route::post('/periods', [PayrollController::class, 'storePeriod'])
            ->middleware('permission:payroll.period-manage')
            ->name('periods.store');

        Route::post('/periods/{period}/calculate', [PayrollController::class, 'calculate'])
            ->middleware('permission:payroll.calculate')
            ->name('calculate');

        Route::post('/periods/{period}/approve', [PayrollController::class, 'approve'])
            ->middleware('permission:payroll.approve')
            ->name('approve');

        Route::get('/my-slips', [PayrollController::class, 'mySlips'])
            ->middleware('permission:payroll.view-my-slip')
            ->name('my-slips');

        Route::get('/slips/{slip}', [PayrollController::class, 'showSlip'])
            ->middleware('permission:payroll.view-my-slip')
            ->name('slips.show');
    });

    // 6. Finance & Auto-Journaling
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/vouchers', [VoucherController::class, 'index'])
            ->middleware('permission:finance.journal-view')
            ->name('vouchers.index');

        Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])
            ->middleware('permission:finance.journal-view')
            ->name('vouchers.show');

        Route::get('/vouchers/{voucher}/export/bca', [VoucherController::class, 'exportBca'])
            ->middleware('permission:finance.voucher-release')
            ->name('vouchers.export.bca');

        Route::get('/vouchers/{voucher}/export/mandiri', [VoucherController::class, 'exportMandiri'])
            ->middleware('permission:finance.voucher-release')
            ->name('vouchers.export.mandiri');

        Route::post('/vouchers/{voucher}/reconcile', [VoucherController::class, 'reconcile'])
            ->middleware('permission:finance.voucher-release')
            ->name('vouchers.reconcile');

        Route::get('/ledger', [GeneralLedgerController::class, 'index'])
            ->middleware('permission:finance.journal-view')
            ->name('ledger.index');

        Route::get('/reimbursements', [ReimbursementController::class, 'index'])
            ->name('reimbursements.index');

        Route::post('/reimbursements', [ReimbursementController::class, 'store'])
            ->name('reimbursements.store');

        Route::post('/reimbursements/{reimbursement}/approve', [ReimbursementController::class, 'approveByFinance'])
            ->middleware('permission:finance.voucher-release')
            ->name('reimbursements.approve');
    });

    // 7. Pusat Dokumen & Kebijakan
    Route::post('/documents/{document}/acknowledge', [DocumentController::class, 'acknowledge'])
        ->name('documents.acknowledge');

    // 8. Tata Kelola & Keamanan (Super Admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles-permissions', [GovernanceController::class, 'rbac'])->name('rbac.index');
        Route::get('/audit-trails', [GovernanceController::class, 'auditTrails'])->name('audit.index');
    });
});
