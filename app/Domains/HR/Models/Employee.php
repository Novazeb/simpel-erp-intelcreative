<?php

namespace App\Domains\HR\Models;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\Payroll\Models\PayrollSlip;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'nik',
        'full_name',
        'email',
        'phone',
        'department_id',
        'designation_id',
        'work_shift_id',
        'employment_status',
        'join_date',
        'resign_date',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'basic_salary',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'resign_date' => 'date',
            'basic_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrollSlips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class);
    }
}

