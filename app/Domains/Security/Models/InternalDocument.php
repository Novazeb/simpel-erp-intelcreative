<?php

namespace App\Domains\Security\Models;

use App\Domains\HR\Models\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalDocument extends Model
{
    use HasUuids;

    protected $table = 'internal_documents';

    protected $fillable = [
        'document_number',
        'title',
        'category',
        'version',
        'file_path',
        'requires_acknowledgment',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'requires_acknowledgment' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'uploaded_by');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(DocumentAcknowledgment::class);
    }
}
