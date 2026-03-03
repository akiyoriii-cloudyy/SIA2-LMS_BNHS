<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SchoolYear;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectTeacherController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $selectedSchoolYear = (int) ($request->integer('school_year_id')
            ?: ($schoolYears->firstWhere('is_active', true)?->id ?? $schoolYears->first()?->id ?? 0));
        $quarter = max(1, min(4, (int) $request->integer('quarter', 1)));

        $assignments = SubjectAssignment::query()
            ->with(['subject:id,code,title', 'section:id,name,grade_level,strand', 'schoolYear:id,name'])
            ->where('school_year_id', $selectedSchoolYear)
            ->when($user?->hasRole('teacher') && ! $user->hasRole('admin'), function ($q) use ($user): void {
                $teacherId = $user->teacher?->id;
                if ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderBy('section_id')
            ->orderBy('subject_id')
            ->get();

        $selectedAssignmentId = (int) ($request->integer('assignment_id') ?: ($assignments->first()?->id ?? 0));
        $selectedAssignment = $assignments->firstWhere('id', $selectedAssignmentId);

        $enrollments = collect();
        $missingStudents = [];
        $recentChanges = [];
        $completionPct = 0;
        $missingCount = 0;
        $classAverage = null;
        $lastUpdated = null;
        $openGradebookUrl = route('gradebook.index');

        if ($selectedAssignment) {
            $enrollments = Enrollment::query()
                ->with(['student', 'section'])
                ->where('school_year_id', $selectedAssignment->school_year_id)
                ->where('section_id', $selectedAssignment->section_id)
                ->orderBy('id')
                ->get();

            $gradeEntries = GradeEntry::query()
                ->where('subject_assignment_id', $selectedAssignment->id)
                ->where('quarter', $quarter)
                ->orderByDesc('updated_at')
                ->get()
                ->keyBy('enrollment_id');

            $completeCount = 0;
            $sum = 0.0;

            foreach ($enrollments as $enrollment) {
                $grade = $gradeEntries->get($enrollment->id);
                $isComplete = $grade
                    && $grade->quiz !== null
                    && $grade->assignment !== null
                    && $grade->exam !== null
                    && $grade->quarter_grade !== null;

                if ($isComplete) {
                    $completeCount++;
                    $sum += (float) $grade->quarter_grade;

                    if (! $lastUpdated || ($grade->updated_at && $grade->updated_at->gt($lastUpdated))) {
                        $lastUpdated = $grade->updated_at;
                    }

                    continue;
                }

                $missingStudents[] = [
                    'name' => (string) ($enrollment->student?->full_name ?? 'Student'),
                    'section' => $selectedAssignment->section
                        ? ('Grade '.$selectedAssignment->section->grade_level.' - '.$selectedAssignment->section->name)
                        : '-',
                    'strand' => (string) ($selectedAssignment->section?->strand ?? '-'),
                ];
            }

            $totalStudents = (int) $enrollments->count();
            $missingCount = (int) count($missingStudents);
            $completionPct = $totalStudents > 0 ? (int) round(($completeCount / $totalStudents) * 100) : 0;
            $classAverage = $completeCount > 0 ? round($sum / $completeCount, 1) : null;

            $recentChanges = GradeEntry::query()
                ->with('enrollment.student')
                ->where('subject_assignment_id', $selectedAssignment->id)
                ->where('quarter', $quarter)
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get()
                ->map(function (GradeEntry $entry): array {
                    return [
                        'student' => (string) ($entry->enrollment?->student?->full_name ?? 'Student'),
                        'quarter_grade' => $entry->quarter_grade !== null ? (float) $entry->quarter_grade : null,
                        'updated_at' => $entry->updated_at,
                    ];
                })
                ->all();

            $openGradebookUrl = route('gradebook.index', [
                'school_year_id' => $selectedAssignment->school_year_id,
                'section_id' => $selectedAssignment->section_id,
                'subject_id' => $selectedAssignment->subject_id,
                'quarter' => $quarter,
            ]);
        }

        return view('subject-teacher.index', [
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $selectedSchoolYear,
            'quarter' => $quarter,
            'assignments' => $assignments,
            'selectedAssignmentId' => $selectedAssignmentId,
            'selectedAssignment' => $selectedAssignment,
            'enrollments' => $enrollments,
            'missingStudents' => $missingStudents,
            'recentChanges' => $recentChanges,
            'stats' => [
                'completion_pct' => $completionPct,
                'missing' => $missingCount,
                'class_avg' => $classAverage,
                'last_updated' => $lastUpdated,
                'students' => (int) $enrollments->count(),
            ],
            'openGradebookUrl' => $openGradebookUrl,
        ]);
    }
}
