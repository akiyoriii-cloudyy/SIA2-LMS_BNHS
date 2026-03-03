<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'lrn',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'date_of_birth',
        'address',
        'ethnicity',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_students')
            ->withPivot(['relationship', 'is_primary', 'receive_sms'])
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name . ',',
            $this->first_name,
            $this->middle_name,
            $this->suffix,
        ])));
    }

    public function ageAt(?CarbonInterface $cutoffDate = null): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        $asOf = $cutoffDate ?: now();

        return $this->date_of_birth->diffInYears($asOf);
    }
}
