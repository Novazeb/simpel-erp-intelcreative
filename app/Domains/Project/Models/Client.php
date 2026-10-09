<?php

namespace App\Domains\Project\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasUuids;

    protected $table = 'clients';

    protected $fillable = [
        'company_name',
        'email',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
