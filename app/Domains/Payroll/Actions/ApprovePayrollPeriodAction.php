<?php

namespace App\Domains\Payroll\Actions;

use App\Domains\Finance\Actions\GeneratePayrollAutoJournalAction;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovePayrollPeriodAction
{
    public function __construct(
        protected GeneratePayrollAutoJournalAction $autoJournalAction
    ) {}

    public function execute(PayrollPeriod $period, User $approver): void
    {
        if ($period->status === 'APPROVED') {
            return;
        }

        DB::transaction(function () use ($period, $approver) {
            $period->update([
                'status' => 'APPROVED',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Memicu penerbitan Voucher & Auto-Journal (Sesuai business-workflow.md)
            $this->autoJournalAction->execute($period);
        });
    }
}
