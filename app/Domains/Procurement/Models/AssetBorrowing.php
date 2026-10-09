<?php

namespace App\Domains\Procurement\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetBorrowing extends Model
{
    use HasUuids;

    protected $table = 'asset_borrowings';

    protected $fillable = [
        'asset_item_id',
        'employee_id',
        'borrowed_at',
        'expected_return_at',
        'returned_at',
        'notes',
        'condition_before',
        'condition_after',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'expected_return_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'processed_by');
    }
}
