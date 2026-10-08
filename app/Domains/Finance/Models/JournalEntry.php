<?php

namespace App\Domains\Finance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasUuids;

    protected $table = 'journal_entries';

    public $timestamps = false;

    protected $fillable = [
        'entry_number',
        'reference_type',
        'reference_id',
        'transaction_date',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'journal_entry_id');
    }
}

