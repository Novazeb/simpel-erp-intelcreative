<?php

namespace App\Domains\Attendance\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApprovalStep extends Model
{
    use HasUuids;

    protected $table = 'leave_approval_steps';

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'step_level',
        'status',
        'note',
        'action_at',
    ];

    protected function casts(): array
    {
        return [
            'step_level' => 'integer',
            'action_at' => 'datetime',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}

