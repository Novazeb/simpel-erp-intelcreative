<?php

namespace App\Domains\Finance\Models;

use App\Domains\HR\Models\Department;
use App\Domains\Project\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasSubscription extends Model
{
    use HasUuids;

    protected $table = 'saas_subscriptions';

    protected $fillable = [
        'software_name',
        'vendor_name',
        'billing_cycle',
        'cost_per_cycle',
        'total_seats',
        'start_date',
        'next_billing_date',
        'assigned_department_id',
        'project_id',
        'payment_method',
        'status',
        'auto_renew',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_cycle' => 'decimal:2',
            'total_seats' => 'integer',
            'start_date' => 'date',
            'next_billing_date' => 'date',
            'auto_renew' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
