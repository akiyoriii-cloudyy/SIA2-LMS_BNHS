<?php

namespace App\Services;

use App\Models\AttendanceMonthlyReport;
use App\Models\AttendanceMonthlyReportLine;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceMonthlyReportService
{
    /**
     * @return Collection<int, int>
     */
    public function adviserSectionIds(User $user, ?int $schoolYearId = null): Collection
    {
        if (! $user->teacher) {
            return collect();
        }

        $query = SubjectAssignment::query()
            ->where('teacher_id', $user->teacher->id);

        if ($schoolYearId) {
            $query->where('school_year_id', $schoolYearId);
        }

        return $query->pluck('section_id')->unique()->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summarizeEnrollmentMonth(Enrollment $enrollment, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $rows = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get(['attendance_date', 'status']);

        $byDate = $rows->groupBy(fn ($row) => Carbon::parse((string) $row->attendance_date)->toDateString());

        $countStatus = function (string $status) use ($byDate): int {
            return (int) $byDate->filter(function (Collection $dayRows) use ($status): bool {
                return $dayRows->contains('status', $status);
            })->count();
        };

        return [
            'school_days' => $byDate->count(),
            'present_days' => $countStatus('present'),
            'absent_days' => $countStatus('absent'),
            'late_days' => $countStatus('late'),
            'excused_days' => $countStatus('excused'),
        ];
    }

    public function monthSchoolDaysTotal(int $sectionId, int $schoolYearId, int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $enrollmentIds = Enrollment::query()
            ->where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->pluck('id');

        if ($enrollmentIds->isEmpty()) {
            return 0;
        }

        return (int) AttendanceRecord::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->distinct('attendance_date')
            ->count('attendance_date');
    }

    public function generateOrRefresh(
        Teacher $teacher,
        Section $section,
        SchoolYear $schoolYear,
        int $year,
        int $month,
        User $actor,
        bool $refreshFromRecords = true,
    ): AttendanceMonthlyReport {
        $report = AttendanceMonthlyReport::query()->firstOrNew([
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'report_year' => $year,
            'report_month' => $month,
        ]);

        $report->created_by = $report->exists ? $report->created_by : $actor->id;
        $report->status = AttendanceMonthlyReport::STATUS_DRAFT;
        $report->school_days_total = $this->monthSchoolDaysTotal($section->id, $schoolYear->id, $year, $month);
        $report->generated_at = now();
        $report->save();

        $enrollments = Enrollment::query()
            ->with('student')
            ->where('section_id', $section->id)
            ->where('school_year_id', $schoolYear->id)
            ->orderBy('id')
            ->get();

        $existingLines = $report->lines()->get()->keyBy('enrollment_id');

        foreach ($enrollments as $index => $enrollment) {
            $student = $enrollment->student;
            $summary = $this->summarizeEnrollmentMonth($enrollment, $year, $month);

            /** @var AttendanceMonthlyReportLine|null $line */
            $line = $existingLines->get($enrollment->id);

            if (! $refreshFromRecords) {
                AttendanceMonthlyReportLine::query()->updateOrCreate(
                    [
                        'attendance_monthly_report_id' => $report->id,
                        'enrollment_id' => $enrollment->id,
                    ],
                    [
                        'student_name' => $student?->full_name ?? 'Student',
                        'lrn' => $student?->lrn,
                        'school_days' => $line?->school_days ?? 0,
                        'present_days' => $line?->present_days ?? 0,
                        'absent_days' => $line?->absent_days ?? 0,
                        'late_days' => $line?->late_days ?? 0,
                        'excused_days' => $line?->excused_days ?? 0,
                        'remarks' => $line?->remarks,
                        'sort_order' => $index + 1,
                    ],
                );

                continue;
            }

            AttendanceMonthlyReportLine::query()->updateOrCreate(
                [
                    'attendance_monthly_report_id' => $report->id,
                    'enrollment_id' => $enrollment->id,
                ],
                [
                    'student_name' => $student?->full_name ?? 'Student',
                    'lrn' => $student?->lrn,
                    'school_days' => $summary['school_days'],
                    'present_days' => $summary['present_days'],
                    'absent_days' => $summary['absent_days'],
                    'late_days' => $summary['late_days'],
                    'excused_days' => $summary['excused_days'],
                    'remarks' => $line?->remarks,
                    'sort_order' => $index + 1,
                ],
            );
        }

        return $report->fresh(['lines', 'section', 'schoolYear', 'teacher.user']);
    }

    public function reportHasGeneratedTotals(AttendanceMonthlyReport $report): bool
    {
        $report->loadMissing('lines');

        return $report->lines->contains(
            fn ($line): bool => (int) $line->school_days > 0
                || (int) $line->present_days > 0
                || (int) $line->absent_days > 0
                || (int) $line->late_days > 0
                || (int) $line->excused_days > 0,
        );
    }

    /**
     * @return Collection<int, int>
     */
    public function enrollmentIdsForReport(AttendanceMonthlyReport $report): Collection
    {
        return Enrollment::query()
            ->where('section_id', $report->section_id)
            ->where('school_year_id', $report->school_year_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Live totals from daily attendance_records (web + mobile class roster).
     *
     * @return array<int, array<string, int>> enrollment_id => summary
     */
    public function liveSummariesByEnrollmentId(AttendanceMonthlyReport $report): array
    {
        $year = (int) $report->report_year;
        $month = (int) $report->report_month;
        $summaries = [];

        foreach ($this->enrollmentIdsForReport($report) as $enrollmentId) {
            $enrollment = Enrollment::query()->find($enrollmentId);
            if (! $enrollment) {
                continue;
            }

            $summaries[$enrollmentId] = $this->summarizeEnrollmentMonth($enrollment, $year, $month);
        }

        return $summaries;
    }

    public function liveTotalAbsentDays(AttendanceMonthlyReport $report): int
    {
        return (int) collect($this->liveSummariesByEnrollmentId($report))->sum('absent_days');
    }

    public function liveMonthSchoolDaysTotal(AttendanceMonthlyReport $report): int
    {
        return (int) AttendanceRecord::query()
            ->whereIn('enrollment_id', $this->enrollmentIdsForReport($report)->all())
            ->whereBetween('attendance_date', [
                Carbon::create((int) $report->report_year, (int) $report->report_month, 1)->startOfMonth()->toDateString(),
                Carbon::create((int) $report->report_year, (int) $report->report_month, 1)->endOfMonth()->toDateString(),
            ])
            ->distinct('attendance_date')
            ->count('attendance_date');
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeLineForDisplay(
        AttendanceMonthlyReportLine $line,
        array $liveSummary,
        array $attendanceByDay,
    ): array {
        return [
            'id' => $line->id,
            'enrollment_id' => $line->enrollment_id,
            'student_name' => $line->student_name,
            'lrn' => $line->lrn,
            'school_days' => (int) ($liveSummary['school_days'] ?? 0),
            'present_days' => (int) ($liveSummary['present_days'] ?? 0),
            'absent_days' => (int) ($liveSummary['absent_days'] ?? 0),
            'late_days' => (int) ($liveSummary['late_days'] ?? 0),
            'excused_days' => (int) ($liveSummary['excused_days'] ?? 0),
            'remarks' => $line->remarks,
            'attendance_by_day' => (object) $attendanceByDay,
        ];
    }

    public function userCanAccess(User $user, AttendanceMonthlyReport $report): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->teacher
            && (int) $user->teacher->id === (int) $report->teacher_id;
    }

    /**
     * @return array<int, array<string, string>> enrollment_id => ['01' => 'present', ...]
     */
    public function attendanceByEnrollmentForReportMonth(AttendanceMonthlyReport $report): array
    {
        $enrollmentIds = $this->enrollmentIdsForReport($report);
        if ($enrollmentIds->isEmpty()) {
            return [];
        }

        $start = Carbon::create((int) $report->report_year, (int) $report->report_month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = AttendanceRecord::query()
            ->whereIn('enrollment_id', $enrollmentIds->all())
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('attendance_date')
            ->get(['enrollment_id', 'attendance_date', 'status']);

        return $records
            ->groupBy('enrollment_id')
            ->map(function (Collection $rows): array {
                return $rows
                    ->sortBy('attendance_date')
                    ->mapWithKeys(function (AttendanceRecord $row): array {
                        $day = str_pad((string) Carbon::parse((string) $row->attendance_date)->day, 2, '0', STR_PAD_LEFT);

                        return [$day => strtolower((string) $row->status)];
                    })
                    ->all();
            })
            ->all();
    }

    public function ensureCurrentMonthReports(User $actor, ?int $schoolYearId = null): void
    {
        $teacher = $actor->teacher;
        if (! $teacher) {
            return;
        }

        $now = now();
        $year = (int) $now->year;
        $month = (int) $now->month;

        $assignments = SubjectAssignment::query()
            ->select(['section_id', 'school_year_id'])
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('section_id')
            ->whereNotNull('school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->get()
            ->unique(fn ($row) => $row->section_id.'|'.$row->school_year_id)
            ->values();

        foreach ($assignments as $assignment) {
            $section = Section::query()->find((int) $assignment->section_id);
            $schoolYear = SchoolYear::query()->find((int) $assignment->school_year_id);
            if (! $section || ! $schoolYear) {
                continue;
            }

            $this->generateOrRefresh(
                $teacher,
                $section,
                $schoolYear,
                $year,
                $month,
                $actor,
                false,
            );
        }
    }

    public static function statusMarker(string $status): string
    {
        return match (strtolower($status)) {
            'present' => 'P',
            'late' => 'L',
            'excused' => 'E',
            'absent' => 'A',
            default => strtoupper(substr($status, 0, 1)),
        };
    }

    /**
     * @param  array<string, string>  $byDay
     */
    public static function formatAttendanceByDay(array $byDay): string
    {
        if ($byDay === []) {
            return '—';
        }

        ksort($byDay, SORT_NATURAL);

        return collect($byDay)
            ->map(fn (string $status, string $day): string => $day.':'.self::statusMarker($status))
            ->implode(' ');
    }
}
