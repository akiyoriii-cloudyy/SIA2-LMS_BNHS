<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardItem extends Model
{
    protected $fillable = [
        'report_card_id',
        'subject_assignment_id',
        'q1',
        'q2',
        'q3',
        'q4',
        'final_grade',
    ];

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }
}

