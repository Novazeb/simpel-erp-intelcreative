<?php

namespace App\Domains\Payroll\Actions;

use App\Domains\HR\Models\Employee;
use App\Domains\Payroll\Jobs\CalculatePayrollChunkJob;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Services\PayrollCalculationEngine;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculatePayrollBatchAction
{
    public function __construct(
        protected PayrollCalculationEngine $calculationEngine
    ) {}

    /**
     * Memproses kalkulasi batch menggunakan Laravel Job Batching & Chunking (umpanbalik.md Bagian 2.A)
     * Mencegah memory leak dan timeout pada 1.000+ karyawan.
     */
    public function execute(PayrollPeriod $period, bool $synchronous = false): ?Batch
    {
        if (in_array($period->status, ['APPROVED', 'PAID'])) {
            throw new \Exception('Periode payroll telah dikunci dan tidak dapat dihitung ulang.');
        }

        $period->update(['status' => 'CALCULATING']);

        $employeeIds = Employee::where('employment_status', '!=', 'RESIGNED')
            ->where('join_date', '<=', $period->end_date)
            ->pluck('id');

        if ($employeeIds->isEmpty()) {
            $period->update(['status' => 'CALCULATED']);

            return null;
        }

        // Chunk per 50 karyawan
        $chunks = $employeeIds->chunk(50);
        $jobs = $chunks->map(fn ($chunk) => new CalculatePayrollChunkJob($chunk, $period->id));

        if ($synchronous || app()->runningUnitTests()) {
            // Mode sinkron untuk unit/feature test atau CLI instan
            foreach ($jobs as $job) {
                $job->handle($this->calculationEngine);
            }
            $period->update(['status' => 'CALCULATED']);

            return null;
        }

        // Dispatch via Laravel Job Batching
        $batch = Bus::batch($jobs)
            ->name("Payroll Batch: {$period->name}")
            ->then(function (Batch $batch) use ($period) {
                $period->update(['status' => 'CALCULATED']);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error("Gagal memproses batch payroll {$batch->id}: ".$e->getMessage());
            })
            ->dispatch();

        return $batch;
    }
}
