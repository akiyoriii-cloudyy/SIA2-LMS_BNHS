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

            if ($line && ! $refreshFromRecords) {
                $line->update([
                    'student_name' => $student?->full_name ?? $line->student_name,
                    'lrn' => $student?->lrn ?? $line->lrn,
                    'sort_order' => $index + 1,
                ]);

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

    public function userCanAccess(User $user, AttendanceMonthlyReport $report): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->teacher
            && (int) $user->teacher->id === (int) $report->teacher_id;
    }
}
