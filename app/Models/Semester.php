<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'name',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'bool',
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
