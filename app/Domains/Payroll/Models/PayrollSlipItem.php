<?php

namespace App\Domains\Payroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSlipItem extends Model
{
    use HasUuids;

    protected $table = 'payroll_slip_items';

    public $timestamps = false;

    protected $fillable = [
        'payroll_slip_id',
        'item_type',
        'name',
        'amount',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function slip(): BelongsTo
    {
        return $this->belongsTo(PayrollSlip::class, 'payroll_slip_id');
    }
}

