<?php

namespace App\Domains\Procurement\Models;

use App\Domains\Finance\Models\JournalEntry;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends Model
{
    use HasUuids;

    protected $table = 'asset_depreciations';

    protected $fillable = [
        'asset_item_id',
        'depreciation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value_after',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'depreciation_date' => 'date',
            'depreciation_amount' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'book_value_after' => 'decimal:2',
        ];
    }

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
