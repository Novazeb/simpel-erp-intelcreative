<?php

namespace App\Domains\Attendance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasUuids;

    protected $table = 'leave_types';

    protected $fillable = [
        'code',
        'name',
        'is_paid',
        'default_quota_days',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'default_quota_days' => 'integer',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
