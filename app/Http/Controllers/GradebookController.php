<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function index(Request $request): View
    {
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $sections = Section::query()->orderBy('grade_level')->orderBy('name')->get();
        $subjects = Subject::query()->orderBy('title')->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        $selectedSection = (int) ($request->integer('section_id') ?: ($sections->first()?->id ?? 0));
        $selectedSubject = (int) ($request->integer('subject_id') ?: ($subjects->first()?->id ?? 0));
        $quarter = max(1, min(4, (int) $request->integer('quarter', 1)));

        $subjectAssignment = SubjectAssignment::query()
            ->where('school_year_id', $selectedSchoolYear)
            ->where('section_id', $selectedSection)
            ->where('subject_id', $selectedSubject)
            ->first();

        $enrollments = collect();
        $existingGrades = collect();

        if ($selectedSchoolYear && $selectedSection) {
            $enrollments = Enrollment::query()
                ->with('student')
                ->where('school_year_id', $selectedSchoolYear)
                ->where('section_id', $selectedSection)
                ->orderBy('id')
                ->get();
        }

        if ($subjectAssignment) {
            $existingGrades = GradeEntry::query()
                ->where('subject_assignment_id', $subjectAssignment->id)
                ->where('quarter', $quarter)
                ->get()
                ->keyBy('enrollment_id');
        }

        return view('gradebook.index', [
            'schoolYears' => $schoolYears,
            'sections' => $sections,
            'subjects' => $subjects,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'selectedSubject' => $selectedSubject,
            'quarter' => $quarter,
            'enrollments' => $enrollments,
            'existingGrades' => $existingGrades,
            'subjectAssignment' => $subjectAssignment,
        ]);
    }

    public function store(Request $request, GradingService $gradingService): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'grades' => ['nullable', 'array'],
            'grades.*.quiz' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.assignment' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.exam' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $subjectAssignment = SubjectAssignment::firstOrCreate([
            'school_year_id' => (int) $validated['school_year_id'],
            'section_id' => (int) $validated['section_id'],
            'subject_id' => (int) $validated['subject_id'],
        ]);

        $quarter = (int) $validated['quarter'];
        $gradeRows = $validated['grades'] ?? [];

        foreach ($gradeRows as $enrollmentId => $row) {
            $enrollment = Enrollment::query()
                ->where('id', (int) $enrollmentId)
                ->where('school_year_id', (int) $validated['school_year_id'])
                ->where('section_id', (int) $validated['section_id'])
                ->first();

            if (! $enrollment) {
                continue;
            }

            $gradingService->upsertQuarterGrade(
                $enrollment,
                $subjectAssignment,
                $quarter,
                $this->normalizeScore($row['quiz'] ?? null),
                $this->normalizeScore($row['assignment'] ?? null),
                $this->normalizeScore($row['exam'] ?? null)
            );
        }

        return redirect()
            ->route('gradebook.index', [
                'school_year_id' => $validated['school_year_id'],
                'section_id' => $validated['section_id'],
                'subject_id' => $validated['subject_id'],
                'quarter' => $quarter,
            ])
            ->with('success', 'Grades saved. Subject averages and report cards were updated automatically.');
    }

    private function normalizeScore(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
