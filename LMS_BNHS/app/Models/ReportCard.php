<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    protected $fillable = [
        'enrollment_id',
        'general_average',
        'remarks',
        'observed_values',
    ];

    protected $casts = [
        'observed_values' => 'array',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReportCardItem::class);
    }
}
