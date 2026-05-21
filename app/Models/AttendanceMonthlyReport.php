<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMonthlyReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'teacher_id',
        'section_id',
        'school_year_id',
        'report_year',
        'report_month',
        'status',
        'notes',
        'school_days_total',
        'generated_at',
        'emailed_at',
        'created_by',
    ];

    protected $casts = [
        'report_year' => 'integer',
        'report_month' => 'integer',
        'school_days_total' => 'integer',
        'generated_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AttendanceMonthlyReportLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function periodLabel(): string
    {
        return \Carbon\Carbon::create($this->report_year, $this->report_month, 1)->format('F Y');
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function webUrl(): string
    {
        return route('attendance-reports.show', $this);
    }

    public function printUrl(): string
    {
        return route('attendance-reports.print', $this);
    }
}
