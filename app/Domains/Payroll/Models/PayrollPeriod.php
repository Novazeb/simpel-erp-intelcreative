<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Finance\Models\DisbursementVoucher;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollPeriod extends Model
{
    use HasUuids;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'cutoff_date',
        'payment_date',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'cutoff_date' => 'date',
            'payment_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function slips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(DisbursementVoucher::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
