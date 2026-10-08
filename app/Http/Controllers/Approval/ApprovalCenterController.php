<?php

namespace App\Http\Controllers\Approval;

use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Finance\Models\DisbursementVoucher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalCenterController extends Controller
{
    public function index()
    {
        $pendingLeaves = LeaveRequest::with(['employee', 'leaveType'])
            ->whereIn('status', ['PENDING', 'PENDING_SUPERVISOR', 'PENDING_HOD', 'PENDING_HR'])
            ->latest()
            ->get();

        $calculatedPayrolls = PayrollPeriod::where('status', 'CALCULATED')
            ->latest()
            ->get();

        $draftVouchers = DisbursementVoucher::with('payrollPeriod')
            ->where('status', 'DRAFT')
            ->latest()
            ->get();

        return Inertia::render('Approvals/ApprovalCenter', [
            'pending_leaves' => $pendingLeaves,
            'calculated_payrolls' => $calculatedPayrolls,
            'draft_vouchers' => $draftVouchers,
        ]);
    }
}
