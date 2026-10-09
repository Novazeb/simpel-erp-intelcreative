<?php

namespace App\Domains\Security\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAcknowledgment extends Model
{
    use HasUuids;

    protected $table = 'document_acknowledgments';

    protected $fillable = [
        'internal_document_id',
        'employee_id',
        'acknowledged_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(InternalDocument::class, 'internal_document_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
