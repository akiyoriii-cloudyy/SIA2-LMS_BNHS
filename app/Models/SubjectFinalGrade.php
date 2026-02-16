<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectFinalGrade extends Model
{
    protected $fillable = [
        'enrollment_id',
        'subject_assignment_id',
        'q1',
        'q2',
        'q3',
        'q4',
        'final_grade',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }
}

