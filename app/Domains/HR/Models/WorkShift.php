<?php

namespace App\Domains\HR\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    use HasUuids;

    protected $table = 'work_shifts';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'late_tolerance_minutes',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

