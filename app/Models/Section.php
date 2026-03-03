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
                CASE
                    WHEN grade_level = 11 AND strand = 'HUMSS' THEN 1
                    WHEN grade_level = 11 AND strand = 'ABM' THEN 2
                    WHEN grade_level = 11 AND strand = 'COOKERY/BPP' THEN 3
                    WHEN grade_level = 11 AND strand = 'SMAW' THEN 4
                    WHEN grade_level = 11 AND strand = 'FOP' THEN 5
                    WHEN grade_level = 11 AND strand = 'CSS' THEN 6
                    WHEN grade_level = 12 AND strand = 'CSS' THEN 1
                    WHEN grade_level = 12 AND strand = 'ABM' THEN 2
                    WHEN grade_level = 12 AND strand = 'SMAW' THEN 3
                    WHEN grade_level = 12 AND strand = 'FBS' THEN 4
                    WHEN grade_level = 12 AND strand = 'HUMS' THEN 5
                    WHEN grade_level = 12 AND strand = 'FOP' THEN 6
                    ELSE 99
                END
            ")
            ->orderBy('name');
    }
}
