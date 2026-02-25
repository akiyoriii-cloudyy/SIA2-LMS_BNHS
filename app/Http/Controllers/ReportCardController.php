<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function index(Request $request, GradingService $gradingService): View
    {
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $sections = Section::query()->orderBy('grade_level')->orderBy('name')->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        $selectedSection = (int) ($request->integer('section_id') ?: ($sections->first()?->id ?? 0));

        $enrollments = Enrollment::query()
            ->with(['student', 'reportCard'])
            ->where('school_year_id', $selectedSchoolYear)
            ->where('section_id', $selectedSection)
            ->orderBy('id')
            ->get();

        foreach ($enrollments->whereNull('reportCard') as $enrollment) {
            $gradingService->syncEnrollmentReportCard($enrollment);
        }

        $enrollments->load('reportCard');

        return view('report-cards.index', [
            'schoolYears' => $schoolYears,
            'sections' => $sections,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'enrollments' => $enrollments,
        ]);
    }

    public function show(Enrollment $enrollment, GradingService $gradingService): View
    {
        $enrollment->load(['student', 'section', 'schoolYear']);
        $gradingService->syncEnrollmentReportCard($enrollment);

        $enrollment->load([
            'reportCard.items.subjectAssignment.subject',
            'reportCard.items.subjectAssignment.section',
        ]);

        $peerEnrollments = Enrollment::query()
            ->with('student')
            ->where('school_year_id', $enrollment->school_year_id)
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get();

        $adviserTeacher = SubjectAssignment::query()
            ->with(['teacher.user'])
            ->where('school_year_id', $enrollment->school_year_id)
            ->where('section_id', $enrollment->section_id)
            ->whereNotNull('teacher_id')
            ->orderBy('id')
            ->first()?->teacher;

        $schoolYearName = (string) ($enrollment->schoolYear?->name ?? '');
        $startYear = (int) (explode('-', $schoolYearName)[0] ?? 0);
        $syStart = $startYear > 0 ? Carbon::create($startYear, 6, 1)->startOfDay() : now()->subMonths(10)->startOfMonth();
        $syEnd = $startYear > 0 ? Carbon::create($startYear + 1, 3, 31)->endOfDay() : now()->endOfMonth();

        $attendanceRows = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereBetween('attendance_date', [$syStart->toDateString(), $syEnd->toDateString()])
            ->get(['attendance_date', 'status']);

        $months = [
            ['key' => 'Jun', 'month' => 6],
            ['key' => 'Jul', 'month' => 7],
            ['key' => 'Aug', 'month' => 8],
            ['key' => 'Sep', 'month' => 9],
            ['key' => 'Oct', 'month' => 10],
            ['key' => 'Nov', 'month' => 11],
            ['key' => 'Dec', 'month' => 12],
            ['key' => 'Jan', 'month' => 1],
            ['key' => 'Feb', 'month' => 2],
            ['key' => 'Mar', 'month' => 3],
        ];

        $attendanceSummary = collect($months)->mapWithKeys(function (array $m) use ($attendanceRows): array {
            $filtered = $attendanceRows->filter(function ($row) use ($m): bool {
                $d = $row->attendance_date instanceof Carbon ? $row->attendance_date : Carbon::parse((string) $row->attendance_date);

                return (int) $d->month === (int) $m['month'];
            });

            $schoolDays = (int) $filtered->pluck('attendance_date')->unique()->count();
            $presentDays = (int) $filtered->where('status', 'present')->pluck('attendance_date')->unique()->count();
            $absentDays = (int) $filtered->where('status', 'absent')->pluck('attendance_date')->unique()->count();

            return [
                (string) $m['key'] => [
                    'school_days' => $schoolDays,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                ],
            ];
        })->all();

        return view('report-cards.show', [
            'enrollment' => $enrollment,
            'reportCard' => $enrollment->reportCard,
            'peerEnrollments' => $peerEnrollments,
            'adviserTeacher' => $adviserTeacher,
            'attendanceMonths' => array_column($months, 'key'),
            'attendanceSummary' => $attendanceSummary,
        ]);
    }
}
