<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\GradeEntry;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeSchoolYear = SchoolYear::query()
            ->where('is_active', true)
            ->first() ?? SchoolYear::query()->orderByDesc('name')->first();

        $schoolYearId = $activeSchoolYear?->id;
        $quarter = max(1, min(4, (int) $request->integer('quarter', $this->guessQuarterFromDate(now()))));

        $enrollmentQuery = Enrollment::query()->when(
            $schoolYearId,
            fn ($q) => $q->where('school_year_id', $schoolYearId),
        );

        $applyEnrollmentScope = $user && $user->hasRole('teacher', 'student') && ! $user->hasRole('admin');

        if ($user?->hasRole('teacher') && $user->teacher && $schoolYearId) {
            $teacherSectionIds = SubjectAssignment::query()
                ->where('teacher_id', $user->teacher->id)
                ->where('school_year_id', $schoolYearId)
                ->pluck('section_id')
                ->unique()
                ->values();

            $enrollmentQuery->whereIn('section_id', $teacherSectionIds);
        }

        if ($user?->hasRole('student') && $user->student && $schoolYearId) {
            $enrollmentQuery->where('student_id', $user->student->id);
        }

        $enrollmentIds = $enrollmentQuery->pluck('id');
        $sectionIds = (clone $enrollmentQuery)->pluck('section_id')->unique()->values();

        $totalStudents = Enrollment::query()
            ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('id', $enrollmentIds))
            ->distinct('student_id')
            ->count('student_id');

        $totalSubjects = SubjectAssignment::query()
            ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->when($user?->hasRole('teacher') && $user->teacher, fn ($q) => $q->where('teacher_id', $user->teacher->id))
            ->when($user?->hasRole('student') && $sectionIds->isNotEmpty(), fn ($q) => $q->whereIn('section_id', $sectionIds))
            ->distinct('subject_id')
            ->count('subject_id');

        $recentGrades = GradeEntry::query()
            ->with(['enrollment.student', 'subjectAssignment.subject'])
            ->where('quarter', $quarter)
            ->when($schoolYearId, function ($q) use ($schoolYearId): void {
                $q->whereHas('enrollment', fn ($e) => $e->where('school_year_id', $schoolYearId))
                    ->whereHas('subjectAssignment', fn ($a) => $a->where('school_year_id', $schoolYearId));
            })
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $currentWeekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $absenceAlertsCount = SmsLog::query()
            ->where('week_start', $currentWeekStart)
            ->when($schoolYearId, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('school_year_id', $schoolYearId)))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->count();

        $alertLogs = SmsLog::query()
            ->with('student')
            ->when($schoolYearId, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('school_year_id', $schoolYearId)))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $nearThreshold = AttendanceRecord::query()
            ->select([
                'enrollment_id',
                'school_week_start',
                DB::raw('COUNT(*) as absences_count'),
            ])
            ->where('status', 'absent')
            ->where('school_week_start', $currentWeekStart)
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->groupBy('enrollment_id', 'school_week_start')
            ->having('absences_count', '>=', 4)
            ->having('absences_count', '<', 5)
            ->orderByDesc('absences_count')
            ->limit(3)
            ->get()
            ->load('enrollment.student');

        $attendanceWindowStart = now()->subDays(30)->toDateString();
        $attendanceRecords = AttendanceRecord::query()
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->where('attendance_date', '>=', $attendanceWindowStart);

        $attendanceTotal = (int) $attendanceRecords->count();
        $attendanceAbsent = (int) (clone $attendanceRecords)->where('status', 'absent')->count();
        $attendanceRate = $attendanceTotal > 0
            ? (int) round((($attendanceTotal - $attendanceAbsent) / $attendanceTotal) * 100)
            : 0;

        $stats = [
            'total_students' => $totalStudents,
            'total_subjects' => $totalSubjects,
            'absence_alerts' => $absenceAlertsCount,
            'attendance_rate' => $attendanceRate,
        ];

        $quickLinks = [
            ['label' => 'Courses', 'route' => 'courses.index'],
        ];

        if ($user && $user->hasRole('admin', 'teacher')) {
            $quickLinks = array_merge($quickLinks, [
                ['label' => 'Gradebook', 'route' => 'gradebook.index'],
                ['label' => 'Attendance', 'route' => 'attendance.index'],
                ['label' => 'Report Cards', 'route' => 'report-cards.index'],
                ['label' => 'Database Tables', 'route' => 'system.tables'],
            ]);
        }

        return view('dashboard', [
            'stats' => $stats,
            'quickLinks' => $quickLinks,
            'activeSchoolYear' => $activeSchoolYear,
            'quarter' => $quarter,
            'recentGrades' => $recentGrades,
            'alertLogs' => $alertLogs,
            'nearThreshold' => $nearThreshold,
        ]);
    }

    private function guessQuarterFromDate(Carbon $date): int
    {
        $month = (int) $date->format('n');

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

    public function systemTables(): View
    {
        $tables = [
            [
                'table' => 'school_years',
                'purpose' => 'Defines the active school year.',
                'used_in' => 'Courses, Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'sections',
                'purpose' => 'Class sections (grade level, track/strand).',
                'used_in' => 'Courses, Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'students',
                'purpose' => 'Student master records linked to users.',
                'used_in' => 'Enrollments, Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'teachers',
                'purpose' => 'Teacher master records linked to users.',
                'used_in' => 'Subject assignments, Courses',
            ],
            [
                'table' => 'subjects',
                'purpose' => 'Subject catalog.',
                'used_in' => 'Courses, Gradebook, Report Cards',
            ],
            [
                'table' => 'subject_assignments',
                'purpose' => 'Maps teacher + section + subject for a school year.',
                'used_in' => 'Gradebook, Report Cards',
            ],
            [
                'table' => 'enrollments',
                'purpose' => 'Student enrollment per school year + section.',
                'used_in' => 'Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'courses',
                'purpose' => 'LMS course shell (per section/subject/year).',
                'used_in' => 'Courses list',
            ],
            [
                'table' => 'grade_entries',
                'purpose' => 'Quarter component grades (quiz/assignment/exam).',
                'used_in' => 'Gradebook',
            ],
            [
                'table' => 'subject_final_grades',
                'purpose' => 'Computed subject averages per enrollment.',
                'used_in' => 'Report Cards',
            ],
            [
                'table' => 'report_cards / report_card_items',
                'purpose' => 'Report card header + subject line items.',
                'used_in' => 'Report Cards',
            ],
            [
                'table' => 'attendance_records',
                'purpose' => 'Daily attendance status per student enrollment.',
                'used_in' => 'Attendance Monitoring + SMS triggers',
            ],
            [
                'table' => 'guardians / guardian_students',
                'purpose' => 'Guardian contacts linked to students.',
                'used_in' => 'SMS notifications for absences',
            ],
            [
                'table' => 'roles / user_roles',
                'purpose' => 'Role-based access control (admin/teacher/student).',
                'used_in' => 'Permissions for all modules',
            ],
            [
                'table' => 'users',
                'purpose' => 'Login accounts and basic profile.',
                'used_in' => 'Authentication',
            ],
        ];

        return view('system-tables', ['tables' => $tables]);
    }
}
