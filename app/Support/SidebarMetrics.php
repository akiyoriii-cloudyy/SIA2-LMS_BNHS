<?php

namespace App\Support;

use App\Models\GradeEntry;
use App\Models\ReportCard;
use App\Models\SchoolYear;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SidebarMetrics
{
    public static function currentQuarter(?Carbon $date = null): int
    {
        $month = (int) ($date ?? now())->format('n');

        if (in_array($month, [6, 7, 8], true)) {
            return 1;
        }

        if (in_array($month, [9, 10], true)) {
            return 2;
        }

        if (in_array($month, [11, 12], true)) {
            return 3;
        }

        if (in_array($month, [1, 2, 3], true)) {
            return 4;
        }

        return 1;
    }

    public static function teacherMissingGradesCount(User $user, ?int $quarter = null): int
    {
        if (! $user->hasRole('teacher')) {
            return 0;
        }

        $activeSchoolYear = self::activeSchoolYearId();
        if (! $activeSchoolYear) {
            return 0;
        }

        $quarter = max(1, min(4, (int) ($quarter ?: self::currentQuarter())));

        $assignmentIds = SubjectAssignment::query()
            ->where('school_year_id', $activeSchoolYear)
            ->when($user->hasRole('teacher') && ! $user->hasRole('admin'), function ($q) use ($user): void {
                $teacherId = $user->teacher?->id;
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->pluck('id');

        if ($assignmentIds->isEmpty()) {
            return 0;
        }

        $expected = (int) DB::table('subject_assignments as sa')
            ->join('enrollments as e', function ($join): void {
                $join->on('e.section_id', '=', 'sa.section_id')
                    ->on('e.school_year_id', '=', 'sa.school_year_id');
            })
            ->where('sa.school_year_id', $activeSchoolYear)
            ->whereIn('sa.id', $assignmentIds)
            ->count();

        if ($expected <= 0) {
            return 0;
        }

        $complete = (int) GradeEntry::query()
            ->where('quarter', $quarter)
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->whereNotNull('quiz')
            ->whereNotNull('assignment')
            ->whereNotNull('exam')
            ->whereNotNull('quarter_grade')
            ->count();

        return max(0, $expected - $complete);
    }

    public static function printableReportCardsCount(User $user): int
    {
        if (! $user->hasRole('teacher')) {
            return 0;
        }

        $activeSchoolYear = self::activeSchoolYearId();
        if (! $activeSchoolYear) {
            return 0;
        }

        $query = ReportCard::query()
            ->whereNotNull('general_average')
            ->whereHas('enrollment', fn ($q) => $q->where('school_year_id', $activeSchoolYear));

        if ($user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacherId = $user->teacher?->id;

            if (! $teacherId) {
                return 0;
            }

            $sectionIds = SubjectAssignment::query()
                ->where('school_year_id', $activeSchoolYear)
                ->where('teacher_id', $teacherId)
                ->pluck('section_id')
                ->unique()
                ->values();

            if ($sectionIds->isEmpty()) {
                return 0;
            }

            $query->whereHas('enrollment', fn ($q) => $q->whereIn('section_id', $sectionIds));
        }

        return (int) $query->count();
    }

    private static function activeSchoolYearId(): ?int
    {
        return SchoolYear::query()
            ->where('is_active', true)
            ->value('id')
            ?: SchoolYear::query()->orderByDesc('name')->value('id');
    }
}
