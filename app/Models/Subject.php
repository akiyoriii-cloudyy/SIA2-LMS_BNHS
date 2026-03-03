<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['core', 'applied', 'specialized'];

    protected $fillable = [
        'code',
        'title',
        'category',
    ];

    public function scopeOrderedForDropdown(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE code
                WHEN 'ORALCOMM' THEN 1
                WHEN 'KOMPAN' THEN 2
                WHEN '21CLIT' THEN 3
                WHEN 'CPAR' THEN 4
                WHEN 'MIL' THEN 5
                WHEN 'PERDEV' THEN 6
                WHEN 'ELS' THEN 7
                WHEN 'PEH' THEN 8
                ELSE 99
            END
        ")->orderBy('title');
    }
}
