<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\HR\Models\Employee;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollSlip;
use App\Domains\Payroll\Models\PayrollSlipItem;
use Carbon\CarbonPeriod;

class PayrollCalculationEngine
{
    /**
     * Hitung slip gaji karyawan untuk periode tertentu (Idempotent).
     */
    public function calculateForEmployee(PayrollPeriod $period, Employee $employee): PayrollSlip
    {
        $startDate = $period->start_date->toDateString();
        $endDate = $period->end_date->toDateString();

        // 1. Ambil rekaman kehadiran
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->get();

        $attendanceCount = $attendances->where('status', 'PRESENT')->count();
        $lateMinutesCount = $attendances->sum('late_minutes');
        $overtimeHoursCount = $attendances->sum('overtime_hours');

        // 2. Hitung jumlah hari kerja resmi dalam periode (Senin - Jumat)
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $totalWorkingDays = 0;
        foreach ($dateRange as $dt) {
            if (! $dt->isWeekend()) {
                $totalWorkingDays++;
            }
        }
        $totalWorkingDays = max($totalWorkingDays, 20); // Default fallback 20-22 hari kerja

        // 3. Ambil cuti Unpaid Leave dalam periode (TC-ATT-02)
        $unpaidLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $unpaidDays = $unpaidLeaves->sum('total_days');
        $absentCount = $attendances->where('status', 'ABSENT')->count() + $unpaidDays;

        // Snapshot basic salary (TC-PAY-01)
        $basicSalary = (float) $employee->basic_salary;

        // Cari atau buat slip (Idempotent: TC-PAY-02)
        $slip = PayrollSlip::firstOrNew([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
        ]);

        $slip->basic_salary_snapshot = $basicSalary;
        $slip->attendance_count = $attendanceCount;
        $slip->absent_count = $absentCount;
        $slip->late_minutes_count = $lateMinutesCount;
        $slip->overtime_hours_count = $overtimeHoursCount;
        $slip->save();

        // Bersihkan item lama jika kalkulasi ulang (Idempotent)
        $slip->items()->delete();

        $items = [];
        $totalAllowances = 0.00;
        $totalDeductions = 0.00;

        // Tunjangan Kehadiran / Tunjangan Tetap (Misal tunjangan kehadiran Rp 50.000 / kehadiran)
        if ($attendanceCount > 0) {
            $allowanceAmount = $attendanceCount * 50000.00;
            $items[] = [
                'item_type' => 'ALLOWANCE',
                'name' => 'Tunjangan Transport & Kehadiran',
                'amount' => $allowanceAmount,
            ];
            $totalAllowances += $allowanceAmount;
        }

        // Tunjangan Lembur (Rp 50.000/jam overtime)
        if ($overtimeHoursCount > 0) {
            $overtimePay = round($overtimeHoursCount * 50000.00, 2);
            $items[] = [
                'item_type' => 'OVERTIME',
                'name' => 'Uang Lembur Terverifikasi',
                'amount' => $overtimePay,
            ];
            $totalAllowances += $overtimePay;
        }

        // Potongan Keterlambatan (Deduction: Rp 1.000 / menit)
        if ($lateMinutesCount > 0) {
            $latePenalty = $lateMinutesCount * 1000.00;
            $items[] = [
                'item_type' => 'DEDUCTION',
                'name' => 'Potongan Keterlambatan',
                'amount' => $latePenalty,
            ];
            $totalDeductions += $latePenalty;
        }

        // Potongan Unpaid Leave (TC-ATT-02: (unpaidDays / totalWorkingDays) * basicSalary)
        if ($unpaidDays > 0) {
            $unpaidDeduction = round(($unpaidDays / $totalWorkingDays) * $basicSalary, 2);
            $items[] = [
                'item_type' => 'DEDUCTION',
                'name' => 'Potongan Unpaid Leave',
                'amount' => $unpaidDeduction,
            ];
            $totalDeductions += $unpaidDeduction;
        }

        // PPh 21 TER Regulasi PP 58/2023 & PMK 168/2023
        $gross = $basicSalary + $totalAllowances;
        $taxCalculator = app(Pph21TerCalculator::class);
        $tax = $taxCalculator->calculateMonthlyTax($gross, $employee->tax_status ?? 'TK/0');
        if ($tax > 0) {
            $items[] = [
                'item_type' => 'TAX',
                'name' => 'Pajak PPh 21 (TER Kategori '.($employee->tax_status ?? 'TK/0').')',
                'amount' => $tax,
            ];
            $totalDeductions += $tax;
        }

        // BPJS Ketenagakerjaan & Kesehatan dengan Plafon Cap Maksimum
        $bpjsCalculator = app(BpjsCapCalculator::class);
        $bpjsKetenagakerjaan = $bpjsCalculator->calculateBpjsKetenagakerjaan($basicSalary);
        $bpjsKesehatan = $bpjsCalculator->calculateBpjsKesehatan($basicSalary);

        $items[] = [
            'item_type' => 'BENEFIT',
            'name' => 'Iuran BPJS Ketenagakerjaan (Karyawan - JHT & JP Capped)',
            'amount' => $bpjsKetenagakerjaan,
        ];
        $totalDeductions += $bpjsKetenagakerjaan;

        $items[] = [
            'item_type' => 'BENEFIT',
            'name' => 'Iuran BPJS Kesehatan (Karyawan - Capped 12 Jt)',
            'amount' => $bpjsKesehatan,
        ];
        $totalDeductions += $bpjsKesehatan;

        // Insert items
        foreach ($items as $item) {
            PayrollSlipItem::create([
                'payroll_slip_id' => $slip->id,
                'item_type' => $item['item_type'],
                'name' => $item['name'],
                'amount' => $item['amount'],
                'created_at' => now(),
            ]);
        }

        $takeHomePay = max(0, ($basicSalary + $totalAllowances) - $totalDeductions);

        $slip->total_allowances = $totalAllowances;
        $slip->total_deductions = $totalDeductions;
        $slip->take_home_pay = $takeHomePay;
        $slip->save();

        return $slip;
    }
}
