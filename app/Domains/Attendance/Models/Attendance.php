<?php

namespace App\Domains\Attendance\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasUuids;

    protected $table = 'attendances';

    protected $fillable = [
        'employee_id',
        'work_date',
        'clock_in',
        'clock_out',
        'late_minutes',
        'early_leave_minutes',
        'overtime_hours',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'overtime_hours' => 'decimal:2',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
