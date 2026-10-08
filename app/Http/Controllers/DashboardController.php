<?php

namespace App\Http\Controllers;

use App\Domains\HR\Models\Employee;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Finance\Models\DisbursementVoucher;
use App\Domains\Attendance\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $employeeCount = Employee::count();
        $todayAttendanceCount = Attendance::where('work_date', now()->toDateString())->count();
        $activePayroll = PayrollPeriod::latest('start_date')->first();
        $totalDisbursement = DisbursementVoucher::sum('total_amount');

        return Inertia::render('Dashboard/ExecutiveDashboard', [
            'metrics' => [
                'employee_count' => $employeeCount,
                'today_attendance_count' => $todayAttendanceCount,
                'active_payroll_status' => $activePayroll?->status ?? 'TIDAK ADA',
                'total_disbursement' => (string) $totalDisbursement,
            ],
        ]);
    }
}
