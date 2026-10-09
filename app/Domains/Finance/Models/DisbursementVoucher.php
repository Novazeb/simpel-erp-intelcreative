<?php

namespace App\Domains\Finance\Models;

use App\Domains\Payroll\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementVoucher extends Model
{
    use HasUuids;

    protected $table = 'disbursement_vouchers';

    protected $fillable = [
        'voucher_number',
        'payroll_period_id',
        'payment_date',
        'total_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function journalEntry()
    {
        return $this->hasOne(JournalEntry::class, 'reference_id')
            ->where('reference_type', 'PAYROLL_DISBURSEMENT');
    }
}
