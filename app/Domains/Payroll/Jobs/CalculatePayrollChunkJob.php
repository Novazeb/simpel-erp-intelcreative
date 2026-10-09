<?php

namespace App\Domains\Payroll\Jobs;

use App\Domains\HR\Models\Employee;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Services\PayrollCalculationEngine;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CalculatePayrollChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  Collection<string>  $employeeIds
     */
    public function __construct(
        public Collection $employeeIds,
        public string $payrollPeriodId
    ) {
        $this->onQueue('calculations');
    }

    public function handle(PayrollCalculationEngine $engine): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $period = PayrollPeriod::find($this->payrollPeriodId);
        if (! $period) {
            return;
        }

        $employees = Employee::whereIn('id', $this->employeeIds)->get();

        foreach ($employees as $employee) {
            $engine->calculateForEmployee($period, $employee);
        }
    }
}
