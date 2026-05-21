<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMonthlyReportLine extends Model
{
    protected $fillable = [
        'attendance_monthly_report_id',
        'enrollment_id',
        'student_name',
        'lrn',
        'school_days',
        'present_days',
        'absent_days',
        'late_days',
        'excused_days',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'school_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'late_days' => 'integer',
        'excused_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(AttendanceMonthlyReport::class, 'attendance_monthly_report_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
