<?php

namespace App\Models;

use Carbon\Carbon;
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

    public static function appTimezone(): string
    {
        return (string) config('app.timezone', 'Asia/Manila');
    }

    public function periodStart(): Carbon
    {
        return Carbon::create(
            $this->report_year,
            $this->report_month,
            1,
            0,
            0,
            0,
            self::appTimezone(),
        )->startOfDay();
    }

    public function periodEnd(): Carbon
    {
        return $this->periodStart()->copy()->endOfMonth()->endOfDay();
    }

    public function periodLabel(): string
    {
        return $this->periodStart()->format('F Y');
    }

    public function monthName(): string
    {
        return $this->periodStart()->format('F');
    }

    public function calendarYear(): int
    {
        return (int) $this->report_year;
    }

    /**
     * Human-readable inclusive date range for the report month (school local time).
     */
    public function periodRangeLabel(): string
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();

        if ($start->month === $end->month && $start->year === $end->year) {
            return $start->format('F j').' – '.$end->format('j, Y');
        }

        return $start->format('F j, Y').' – '.$end->format('F j, Y');
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

    public function exportExcelUrl(): string
    {
        return route('attendance-reports.export-excel', $this);
    }

    public function reportsIndexUrl(): string
    {
        return route('attendance-reports.index');
    }
}
