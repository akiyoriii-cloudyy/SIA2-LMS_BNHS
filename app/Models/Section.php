<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'name',
        'grade_level',
        'track',
        'strand',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function scopeOrderedForDropdown(Builder $query): Builder
    {
        return $query
            ->orderBy('grade_level')
            ->orderByRaw("
                CASE strand
                    WHEN 'HUMSS' THEN 1
                    WHEN 'ABM' THEN 2
                    WHEN 'STEM' THEN 3
                    WHEN 'GAS' THEN 4
                    WHEN 'TVL' THEN 5
                    WHEN 'SPORTS' THEN 6
                    ELSE 99
                END
            ")
            ->orderBy('name');
    }
}
