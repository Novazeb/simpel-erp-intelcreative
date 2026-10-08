<?php

namespace App\Http\Controllers\Payroll;

use App\Domains\HR\Models\Employee;
use App\Domains\Payroll\Actions\ApprovePayrollPeriodAction;
use App\Domains\Payroll\Actions\CalculatePayrollBatchAction;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollSlip;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $periods = PayrollPeriod::withCount('slips')
            ->latest('start_date')
            ->get();

        return Inertia::render('Payroll/PeriodManager', [
            'periods' => $periods,
        ]);
    }

    public function mySlips(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->with(['department', 'designation'])
            ->first();

        if (! $employee) {
            return Inertia::render('Payroll/MySalarySnapshot', [
                'employee' => null,
                'slips' => [],
                'latest_slip' => null,
            ]);
        }

        $slips = PayrollSlip::with(['period', 'items'])
            ->where('employee_id', $employee->id)
            ->latest()
            ->get();

        $latestSlip = $slips->first();

        return Inertia::render('Payroll/MySalarySnapshot', [
            'employee' => $employee,
            'slips' => $slips,
            'latest_slip' => $latestSlip,
        ]);
    }

    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'cutoff_date' => 'required|date',
            'payment_date' => 'required|date',
        ]);

        $period = PayrollPeriod::create($validated);

        return redirect()->back()->with('success', 'Periode payroll berhasil dibuat.');
    }

    public function calculate(PayrollPeriod $period, CalculatePayrollBatchAction $action)
    {
        $action->execute($period);

        return response()->json([
            'success' => true,
            'message' => 'Kalkulasi batch payroll selesai dijalankan.',
        ], 202);
    }

    public function approve(Request $request, PayrollPeriod $period, ApprovePayrollPeriodAction $action)
    {
        if ($period->status !== 'CALCULATED') {
            return redirect()->back()->with('error', 'Hanya periode berstatus CALCULATED yang dapat disetujui.');
        }

        $action->execute($period, $request->user());

        return redirect()->back()->with('success', 'Payroll berhasil disetujui dan dikunci. Voucher finansial telah diterbitkan.');
    }

    public function showSlip(Request $request, PayrollSlip $slip)
    {
        if (! $request->user()->can('payroll.view-all-slips')) {
            $employee = Employee::where('user_id', $request->user()->id)
                ->orWhere('email', $request->user()->email)
                ->first();

            if (! $employee || $slip->employee_id !== $employee->id) {
                abort(403, 'Akses ditolak: Anda hanya berhak mengakses slip gaji milik Anda sendiri.');
            }
        }

        $slip->load(['employee.department', 'employee.designation', 'period', 'items']);

        return Inertia::render('Payroll/SlipViewer', [
            'slip' => $slip,
        ]);
    }
}
