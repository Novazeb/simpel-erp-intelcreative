<?php

namespace App\Domains\Finance\Models;

use App\Domains\HR\Models\Employee;
use App\Domains\Project\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reimbursement extends Model
{
    use HasUuids;

    protected $table = 'reimbursements';

    protected $fillable = [
        'claim_number',
        'employee_id',
        'project_id',
        'claim_date',
        'title',
        'total_amount',
        'status',
        'approved_by_supervisor',
        'approved_by_finance',
        'disbursement_voucher_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReimbursementItem::class);
    }

    public function supervisorApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_supervisor');
    }

    public function financeApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_finance');
    }

    public function disbursementVoucher(): BelongsTo
    {
        return $this->belongsTo(DisbursementVoucher::class);
    }
}
