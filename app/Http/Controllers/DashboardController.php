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
use App\Models\ReportCard;
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

        $expectedGradeEntries = (int) DB::table('enrollments as e')
            ->join('subject_assignments as sa', function ($join): void {
                $join->on('sa.section_id', '=', 'e.section_id')
                    ->on('sa.school_year_id', '=', 'e.school_year_id');
            })
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('e.id', $enrollmentIds))
            ->count();

        $filledQuarterGrades = (int) DB::table('grade_entries as ge')
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.quarter_grade')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->count();

        $incompleteGrades = max(0, $expectedGradeEntries - $filledQuarterGrades);

        $classAverage = (float) (DB::table('grade_entries as ge')
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.quarter_grade')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->avg('ge.quarter_grade') ?? 0);

        $prevQuarter = $quarter > 1 ? $quarter - 1 : null;
        $prevClassAverage = $prevQuarter
            ? (float) (DB::table('grade_entries as ge')
                ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
                ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
                ->where('ge.quarter', $prevQuarter)
                ->whereNotNull('ge.quarter_grade')
                ->whereColumn('sa.section_id', 'e.section_id')
                ->whereColumn('sa.school_year_id', 'e.school_year_id')
                ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
                ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
                ->avg('ge.quarter_grade') ?? 0)
            : 0.0;

        $classAverageDelta = $prevQuarter ? ($classAverage - $prevClassAverage) : 0.0;

        $expectedPerEnrollment = DB::table('enrollments as e')
            ->join('subject_assignments as sa', function ($join): void {
                $join->on('sa.section_id', '=', 'e.section_id')
                    ->on('sa.school_year_id', '=', 'e.school_year_id');
            })
            ->select(['e.id', DB::raw('COUNT(sa.id) as expected')])
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('e.id', $enrollmentIds))
            ->groupBy('e.id')
            ->pluck('expected', 'id');

        $enrollmentAverages = DB::table('grade_entries as ge')
            ->select([
                'ge.enrollment_id',
                DB::raw('AVG(ge.quarter_grade) as avg_grade'),
                DB::raw('COUNT(*) as entries_count'),
            ])
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.quarter_grade')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->groupBy('ge.enrollment_id')
            ->get();

        $passingCount = 0;
        foreach ($enrollmentAverages as $row) {
            $expected = (int) ($expectedPerEnrollment[$row->enrollment_id] ?? 0);
            if ($expected <= 0) {
                continue;
            }

            $avg = (float) $row->avg_grade;
            $entriesCount = (int) $row->entries_count;

            if ($entriesCount >= $expected && $avg >= 75) {
                $passingCount++;
            }
        }

        $passRate = $totalStudents > 0 ? (int) round(($passingCount / $totalStudents) * 100) : 0;

        $subjectAverages = DB::table('grade_entries as ge')
            ->select([
                's.title as subject_title',
                's.code as subject_code',
                DB::raw('AVG(ge.quarter_grade) as avg_grade'),
            ])
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->join('subjects as s', 'sa.subject_id', '=', 's.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.quarter_grade')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->groupBy('s.id', 's.title', 's.code')
            ->orderByDesc('avg_grade')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                $avg = (float) $r->avg_grade;
                $status = $avg >= 90 ? 'EXCELLENT' : ($avg >= 85 ? 'GOOD' : ($avg >= 75 ? 'SATISFACTORY' : 'NEEDS ATTN'));
                return [
                    'title' => (string) $r->subject_title,
                    'code' => (string) $r->subject_code,
                    'avg' => $avg,
                    'status' => $status,
                ];
            })
            ->values()
            ->all();

        $distribution = [
            'outstanding' => 0,
            'very_good' => 0,
            'satisfactory' => 0,
            'below_75' => 0,
        ];

        foreach ($enrollmentAverages as $row) {
            $expected = (int) ($expectedPerEnrollment[$row->enrollment_id] ?? 0);
            if ($expected <= 0) {
                continue;
            }

            if ((int) $row->entries_count < $expected) {
                continue;
            }

            $avg = (float) $row->avg_grade;

            if ($avg >= 90) {
                $distribution['outstanding']++;
            } elseif ($avg >= 85) {
                $distribution['very_good']++;
            } elseif ($avg >= 75) {
                $distribution['satisfactory']++;
            } else {
                $distribution['below_75']++;
            }
        }

        $quarterCompletion = [];
        for ($q = 1; $q <= 4; $q++) {
            $filled = (int) DB::table('grade_entries as ge')
                ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
                ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
                ->where('ge.quarter', $q)
                ->whereNotNull('ge.quarter_grade')
                ->whereColumn('sa.section_id', 'e.section_id')
                ->whereColumn('sa.school_year_id', 'e.school_year_id')
                ->when($schoolYearId, fn ($qq) => $qq->where('e.school_year_id', $schoolYearId))
                ->when($applyEnrollmentScope, fn ($qq) => $qq->whereIn('ge.enrollment_id', $enrollmentIds))
                ->count();

            $pct = $expectedGradeEntries > 0 ? (int) round(($filled / $expectedGradeEntries) * 100) : 0;

            $quarterCompletion[] = [
                'quarter' => $q,
                'pct' => max(0, min(100, $pct)),
            ];
        }

        $topPerformers = DB::table('grade_entries as ge')
            ->select([
                'ge.enrollment_id',
                DB::raw('AVG(ge.quarter_grade) as avg_grade'),
                DB::raw('COUNT(*) as entries_count'),
            ])
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.quarter_grade')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->groupBy('ge.enrollment_id')
            ->orderByDesc('avg_grade')
            ->limit(8)
            ->get()
            ->filter(function ($r) use ($expectedPerEnrollment) {
                $expected = (int) ($expectedPerEnrollment[$r->enrollment_id] ?? 0);
                return $expected > 0 && (int) $r->entries_count >= $expected;
            })
            ->values();

        $topEnrollmentIds = $topPerformers->pluck('enrollment_id')->all();
        $enrollmentStudents = Enrollment::query()
            ->with('student')
            ->whereIn('id', $topEnrollmentIds)
            ->get()
            ->keyBy('id');

        $topPerformersData = $topPerformers->map(function ($r) use ($enrollmentStudents) {
            $enrollment = $enrollmentStudents->get($r->enrollment_id);
            return [
                'enrollment_id' => (int) $r->enrollment_id,
                'student' => $enrollment?->student?->full_name ?? '—',
                'avg' => (float) $r->avg_grade,
            ];
        })->all();

        $activity = [];

        foreach ($recentGrades as $row) {
            $student = $row->enrollment?->student?->full_name ?? 'Student';
            $subject = $row->subjectAssignment?->subject?->title ?? 'Subject';
            $activity[] = [
                'type' => 'grade',
                'title' => "Q{$quarter} grade updated",
                'text' => "{$student} — {$subject}",
                'at' => $row->updated_at,
            ];
        }

        $recentReportCards = ReportCard::query()
            ->with('enrollment.student')
            ->when($schoolYearId, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('school_year_id', $schoolYearId)))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->orderByDesc('updated_at')
            ->limit(2)
            ->get();

        foreach ($recentReportCards as $rc) {
            $student = $rc->enrollment?->student?->full_name ?? 'Student';
            $activity[] = [
                'type' => 'report',
                'title' => 'Report card updated',
                'text' => "{$student} — Form 138",
                'at' => $rc->updated_at,
            ];
        }

        foreach ($alertLogs as $log) {
            $student = $log->student?->full_name ?? 'Student';
            $activity[] = [
                'type' => 'alert',
                'title' => 'Absence alert',
                'text' => "{$student} — {$log->absences_count}/5 absences",
                'at' => $log->created_at,
            ];
        }

        usort($activity, fn ($a, $b) => ($b['at']?->getTimestamp() ?? 0) <=> ($a['at']?->getTimestamp() ?? 0));
        $activity = array_slice($activity, 0, 6);

        $quizFilled = (int) DB::table('grade_entries as ge')
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.quiz')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->count();

        $assignmentFilled = (int) DB::table('grade_entries as ge')
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.assignment')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->count();

        $examFilled = (int) DB::table('grade_entries as ge')
            ->join('enrollments as e', 'ge.enrollment_id', '=', 'e.id')
            ->join('subject_assignments as sa', 'ge.subject_assignment_id', '=', 'sa.id')
            ->where('ge.quarter', $quarter)
            ->whereNotNull('ge.exam')
            ->whereColumn('sa.section_id', 'e.section_id')
            ->whereColumn('sa.school_year_id', 'e.school_year_id')
            ->when($schoolYearId, fn ($q) => $q->where('e.school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('ge.enrollment_id', $enrollmentIds))
            ->count();

        $reportCardCompleted = (int) ReportCard::query()
            ->whereNotNull('general_average')
            ->when($schoolYearId, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('school_year_id', $schoolYearId)))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('enrollment_id', $enrollmentIds))
            ->count();

        $enrollmentCount = (int) Enrollment::query()
            ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->when($applyEnrollmentScope, fn ($q) => $q->whereIn('id', $enrollmentIds))
            ->count();

        $submissionStatus = [
            'quiz' => $expectedGradeEntries > 0 ? (int) round(($quizFilled / $expectedGradeEntries) * 100) : 0,
            'assignment' => $expectedGradeEntries > 0 ? (int) round(($assignmentFilled / $expectedGradeEntries) * 100) : 0,
            'exam' => $expectedGradeEntries > 0 ? (int) round(($examFilled / $expectedGradeEntries) * 100) : 0,
            'report_cards' => $enrollmentCount > 0 ? (int) round(($reportCardCompleted / $enrollmentCount) * 100) : 0,
        ];

        $sampleSection = null;
        if ($sectionIds->isNotEmpty()) {
            $sampleSection = Section::query()
                ->whereIn('id', $sectionIds)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->first();
        }

        $scopeLabel = $sampleSection
            ? ('Grade '.$sampleSection->grade_level.' • '.$sampleSection->name.($sectionIds->count() > 1 ? ' +'.($sectionIds->count() - 1).' more' : ''))
            : 'All sections';

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
            'kpis' => [
                'school_year' => $activeSchoolYear?->name ?? '—',
                'scope' => $scopeLabel,
                'class_average' => round($classAverage, 1),
                'class_average_delta' => round($classAverageDelta, 1),
                'passing_count' => $passingCount,
                'pass_rate' => $passRate,
                'incomplete_grades' => $incompleteGrades,
            ],
            'subjectAverages' => $subjectAverages,
            'distribution' => $distribution,
            'quarterCompletion' => $quarterCompletion,
            'topPerformers' => $topPerformersData,
            'activity' => $activity,
            'submissionStatus' => $submissionStatus,
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

        $schemaCards = [
            [
                'name' => 'students',
                'icon' => '👤',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'student_id', 'type' => 'INT AUTO'],
                    ['tag' => null, 'name' => 'lrn', 'type' => 'VARCHAR(15) UNIQUE'],
                    ['tag' => null, 'name' => 'last_name', 'type' => 'VARCHAR(60)'],
                    ['tag' => null, 'name' => 'first_name', 'type' => 'VARCHAR(60)'],
                    ['tag' => null, 'name' => 'middle_name', 'type' => 'VARCHAR(60)'],
                    ['tag' => null, 'name' => 'sex', 'type' => 'ENUM(M,F)'],
                    ['tag' => null, 'name' => 'birth_date', 'type' => 'DATE'],
                    ['tag' => 'FK', 'name' => 'section_id', 'type' => '→ sections'],
                    ['tag' => null, 'name' => 'school_year', 'type' => 'VARCHAR(9)'],
                    ['tag' => null, 'name' => 'track_strand', 'type' => 'VARCHAR(50)'],
                ],
            ],
            [
                'name' => 'subjects',
                'icon' => '📚',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'subject_id', 'type' => 'INT AUTO'],
                    ['tag' => null, 'name' => 'subject_name', 'type' => 'VARCHAR(120)'],
                    ['tag' => null, 'name' => 'subject_code', 'type' => 'VARCHAR(20)'],
                    ['tag' => null, 'name' => 'track_strand', 'type' => 'VARCHAR(50)'],
                    ['tag' => null, 'name' => 'grade_level', 'type' => 'INT (11 or 12)'],
                    ['tag' => null, 'name' => 'semester', 'type' => 'INT (1 or 2)'],
                    ['tag' => null, 'name' => 'quiz_weight', 'type' => 'DECIMAL(4,2)'],
                    ['tag' => null, 'name' => 'assign_weight', 'type' => 'DECIMAL(4,2)'],
                    ['tag' => null, 'name' => 'exam_weight', 'type' => 'DECIMAL(4,2)'],
                ],
            ],
            [
                'name' => 'grades',
                'icon' => '📝',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'grade_id', 'type' => 'INT AUTO'],
                    ['tag' => 'FK', 'name' => 'student_id', 'type' => '→ students'],
                    ['tag' => 'FK', 'name' => 'subject_id', 'type' => '→ subjects'],
                    ['tag' => null, 'name' => 'quarter', 'type' => 'INT (1–4)'],
                    ['tag' => null, 'name' => 'quiz_score', 'type' => 'DECIMAL(5,2)'],
                    ['tag' => null, 'name' => 'assignment_score', 'type' => 'DECIMAL(5,2)'],
                    ['tag' => null, 'name' => 'exam_score', 'type' => 'DECIMAL(5,2)'],
                    ['tag' => null, 'name' => 'quarterly_avg', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'is_passing', 'type' => 'TINYINT(1) [AUTO]'],
                    ['tag' => 'FK', 'name' => 'encoded_by', 'type' => '→ teachers'],
                    ['tag' => null, 'name' => 'encoded_at', 'type' => 'DATETIME'],
                ],
            ],
            [
                'name' => 'report_cards',
                'icon' => '📋',
                'badge' => 'AUTO',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'rc_id', 'type' => 'INT AUTO'],
                    ['tag' => 'FK', 'name' => 'student_id', 'type' => '→ students'],
                    ['tag' => 'FK', 'name' => 'subject_id', 'type' => '→ subjects'],
                    ['tag' => null, 'name' => 'semester', 'type' => 'INT'],
                    ['tag' => null, 'name' => 'q1_grade', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'q2_grade', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'q3_grade', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'q4_grade', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'final_grade', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'remarks', 'type' => 'VARCHAR(20) [AUTO]'],
                ],
            ],
            [
                'name' => 'general_averages',
                'icon' => '📈',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'ga_id', 'type' => 'INT AUTO'],
                    ['tag' => 'FK', 'name' => 'student_id', 'type' => '→ students'],
                    ['tag' => null, 'name' => 'school_year', 'type' => 'VARCHAR(9)'],
                    ['tag' => null, 'name' => 'sem1_average', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'sem2_average', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'general_average', 'type' => 'DECIMAL(5,2) [AUTO]'],
                    ['tag' => null, 'name' => 'descriptor', 'type' => 'VARCHAR(30) [AUTO]'],
                    ['tag' => null, 'name' => 'remarks', 'type' => 'ENUM(Passed,Failed)'],
                    ['tag' => null, 'name' => 'promotion_status', 'type' => 'ENUM [AUTO]'],
                ],
            ],
            [
                'name' => 'teachers',
                'icon' => '🧑‍🏫',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'teacher_id', 'type' => 'INT AUTO'],
                    ['tag' => null, 'name' => 'employee_no', 'type' => 'VARCHAR(20)'],
                    ['tag' => null, 'name' => 'last_name', 'type' => 'VARCHAR(60)'],
                    ['tag' => null, 'name' => 'first_name', 'type' => 'VARCHAR(60)'],
                    ['tag' => null, 'name' => 'email', 'type' => 'VARCHAR(100)'],
                    ['tag' => null, 'name' => 'username', 'type' => 'VARCHAR(50)'],
                    ['tag' => null, 'name' => 'password_hash', 'type' => 'VARCHAR(255)'],
                    ['tag' => null, 'name' => 'role', 'type' => 'ENUM(teacher,admin)'],
                ],
            ],
            [
                'name' => 'sections',
                'icon' => '🏛️',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'section_id', 'type' => 'INT AUTO'],
                    ['tag' => null, 'name' => 'section_name', 'type' => 'VARCHAR(50)'],
                    ['tag' => null, 'name' => 'grade_level', 'type' => 'INT (11 or 12)'],
                    ['tag' => null, 'name' => 'track_strand', 'type' => 'VARCHAR(50)'],
                    ['tag' => 'FK', 'name' => 'adviser_id', 'type' => '→ teachers'],
                    ['tag' => null, 'name' => 'school_year', 'type' => 'VARCHAR(9)'],
                ],
            ],
            [
                'name' => 'teacher_subject_load',
                'icon' => '🔗',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'load_id', 'type' => 'INT AUTO'],
                    ['tag' => 'FK', 'name' => 'teacher_id', 'type' => '→ teachers'],
                    ['tag' => 'FK', 'name' => 'subject_id', 'type' => '→ subjects'],
                    ['tag' => 'FK', 'name' => 'section_id', 'type' => '→ sections'],
                    ['tag' => null, 'name' => 'school_year', 'type' => 'VARCHAR(9)'],
                    ['tag' => null, 'name' => 'semester', 'type' => 'INT'],
                    ['tag' => null, 'name' => 'is_active', 'type' => 'TINYINT(1)'],
                ],
            ],
            [
                'name' => 'audit_logs',
                'icon' => '🗂️',
                'fields' => [
                    ['tag' => 'PK', 'name' => 'log_id', 'type' => 'BIGINT AUTO'],
                    ['tag' => 'FK', 'name' => 'teacher_id', 'type' => '→ teachers'],
                    ['tag' => null, 'name' => 'action', 'type' => 'VARCHAR(50)'],
                    ['tag' => null, 'name' => 'table_affected', 'type' => 'VARCHAR(50)'],
                    ['tag' => null, 'name' => 'record_id', 'type' => 'INT'],
                    ['tag' => null, 'name' => 'old_value', 'type' => 'TEXT'],
                    ['tag' => null, 'name' => 'new_value', 'type' => 'TEXT'],
                    ['tag' => null, 'name' => 'timestamp', 'type' => 'DATETIME'],
                ],
            ],
        ];

        return view('system-tables', [
            'tables' => $tables,
            'schemaCards' => $schemaCards,
        ]);
    }
}
