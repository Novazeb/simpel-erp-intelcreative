<?php

namespace App\Domains\Procurement\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'asset_items';

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'useful_life_years',
        'salvage_value',
        'current_book_value',
        'status',
        'assigned_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'current_book_value' => 'decimal:2',
            'useful_life_years' => 'integer',
        ];
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(AssetBorrowing::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class);
    }
}
