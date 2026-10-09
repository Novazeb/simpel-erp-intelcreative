<?php

namespace App\Domains\Payroll\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSlip extends Model
{
    use HasUuids;

    protected $table = 'payroll_slips';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_salary_snapshot',
        'total_allowances',
        'total_deductions',
        'take_home_pay',
        'attendance_count',
        'absent_count',
        'late_minutes_count',
        'overtime_hours_count',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary_snapshot' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
            'overtime_hours_count' => 'decimal:2',
            'attendance_count' => 'integer',
            'absent_count' => 'integer',
            'late_minutes_count' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollSlipItem::class);
    }
}
