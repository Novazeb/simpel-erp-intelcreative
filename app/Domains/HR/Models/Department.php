<?php

namespace App\Domains\HR\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasUuids;

    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
    ];

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

