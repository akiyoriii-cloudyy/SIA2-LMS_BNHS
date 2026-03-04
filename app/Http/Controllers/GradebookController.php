<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use Illuminate\Http\JsonResponse;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function index(Request $request): View
    {
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $gradeLevels = Section::query()
            ->select('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level')
            ->map(fn ($level) => (int) $level)
            ->values();

        $selectedGradeLevel = (int) ($request->integer('grade_level') ?: ((int) ($gradeLevels->first() ?? 0)));
        if (! $gradeLevels->contains($selectedGradeLevel)) {
            $selectedGradeLevel = (int) ($gradeLevels->first() ?? 0);
        }

        $selectedSubjectCategory = $this->normalizeSubjectCategory((string) $request->query('subject_category', 'core'));
        $subjectCategoryCounts = collect(Subject::CATEGORIES)
            ->mapWithKeys(fn (string $category): array => [$category => (int) Subject::query()->where('category', $category)->count()])
            ->all();

        $sections = Section::query()
            ->orderedForDropdown()
            ->when($selectedGradeLevel > 0, fn ($q) => $q->where('grade_level', $selectedGradeLevel))
            ->get();
        $subjects = Subject::query()
            ->where('category', $selectedSubjectCategory)
            ->orderedForDropdown()
            ->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        $requestedSection = (int) $request->integer('section_id');
        $requestedSubject = (int) $request->integer('subject_id');
        $selectedSection = $sections->contains('id', $requestedSection)
            ? $requestedSection
            : (int) ($sections->first()?->id ?? 0);
        $selectedSubject = $subjects->contains('id', $requestedSubject)
            ? $requestedSubject
            : (int) ($subjects->first()?->id ?? 0);
        $quarterInput = (int) $request->input('quarter', $request->input('current_quarter', 1));
        $quarter = max(1, min(4, $quarterInput));
        $search = trim((string) $request->query('q', ''));

        $subjectAssignment = SubjectAssignment::query()
            ->where('school_year_id', $selectedSchoolYear)
            ->where('section_id', $selectedSection)
            ->where('subject_id', $selectedSubject)
            ->first();

        $enrollments = collect();
        $existingGrades = collect();
        $sectionEnrollmentIds = collect();
        $rosterNumbers = [];
        $sectionTotalStudents = 0;
        $sectionSubjectsCount = 0;
        $pendingGradesCount = 0;

        if ($selectedSchoolYear && $selectedSection) {
            $baseEnrollmentQuery = Enrollment::query()
                ->with('student')
                ->where('school_year_id', $selectedSchoolYear)
                ->where('section_id', $selectedSection);

            $sectionEnrollmentIds = (clone $baseEnrollmentQuery)
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $rosterNumbers = $sectionEnrollmentIds
                ->values()
                ->mapWithKeys(fn (int $enrollmentId, int $index): array => [$enrollmentId => $index + 1])
                ->all();

            $sectionTotalStudents = (clone $baseEnrollmentQuery)
                ->distinct('student_id')
                ->count('student_id');

            if ($search !== '') {
                $baseEnrollmentQuery->whereHas('student', function ($q) use ($search): void {
                    $q->where('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            }

            $enrollments = $baseEnrollmentQuery
                ->orderBy('id')
                ->get();

            $sectionSubjectsQuery = SubjectAssignment::query()
                ->where('school_year_id', $selectedSchoolYear)
                ->where('section_id', $selectedSection);

            $sectionSubjectsQuery->whereHas('subject', fn ($q) => $q->where('category', $selectedSubjectCategory));

            $sectionSubjectsCount = $sectionSubjectsQuery
                ->distinct('subject_id')
                ->count('subject_id');
        }

        if ($subjectAssignment) {
            $existingGrades = GradeEntry::query()
                ->where('subject_assignment_id', $subjectAssignment->id)
                ->where('quarter', $quarter)
                ->get()
                ->keyBy('enrollment_id');
        }

        if ($sectionEnrollmentIds->isNotEmpty()) {
            $pendingGradesCount = (int) $sectionEnrollmentIds
                ->filter(function (int $enrollmentId) use ($existingGrades): bool {
                    $grade = $existingGrades->get($enrollmentId);

                    return ! $grade
                        || $grade->quiz === null
                        || ($grade->performance_task ?? $grade->assignment) === null
                        || $grade->exam === null;
                })
                ->count();
        }

        $selectedSectionModel = $sections->firstWhere('id', $selectedSection);
        $selectedSubjectModel = $subjects->firstWhere('id', $selectedSubject);
        $selectedSchoolYearModel = $schoolYears->firstWhere('id', $selectedSchoolYear);

        return view('gradebook.index', [
            'schoolYears' => $schoolYears,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevel' => $selectedGradeLevel,
            'sections' => $sections,
            'subjects' => $subjects,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'selectedSubject' => $selectedSubject,
            'selectedSubjectCategory' => $selectedSubjectCategory,
            'subjectCategoryCounts' => $subjectCategoryCounts,
            'quarter' => $quarter,
            'rosterNumbers' => $rosterNumbers,
            'enrollments' => $enrollments,
            'existingGrades' => $existingGrades,
            'subjectAssignment' => $subjectAssignment,
            'search' => $search,
            'gradeEntryStats' => [
                'total_students' => $sectionTotalStudents,
                'total_subjects' => $sectionSubjectsCount ?: (int) $subjects->count(),
                'pending' => $pendingGradesCount,
                'school_year' => $selectedSchoolYearModel?->name,
                'section_label' => $selectedSectionModel
                    ? sprintf('Grade %s — %s', $selectedSectionModel->grade_level, $selectedSectionModel->name)
                    : null,
                'subject_title' => $selectedSubjectModel?->title,
            ],
        ]);
    }

    public function store(Request $request, GradingService $gradingService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'grade_level' => ['nullable', 'integer', 'min:1', 'max:12'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'grades' => ['nullable', 'array'],
            'grades.*.quiz' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.performance_task' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.assignment' => ['nullable', 'numeric', 'min:0', 'max:100'], // legacy fallback
            'grades.*.exam' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $subjectAssignment = SubjectAssignment::firstOrCreate([
            'school_year_id' => (int) $validated['school_year_id'],
            'section_id' => (int) $validated['section_id'],
            'subject_id' => (int) $validated['subject_id'],
        ]);

        $quarter = (int) $validated['quarter'];
        $gradeRows = $validated['grades'] ?? [];

        $enrollmentsById = Enrollment::query()
            ->where('school_year_id', (int) $validated['school_year_id'])
            ->where('section_id', (int) $validated['section_id'])
            ->whereIn('id', array_map('intval', array_keys($gradeRows)))
            ->get()
            ->keyBy('id');

        foreach ($gradeRows as $enrollmentId => $row) {
            /** @var Enrollment|null $enrollment */
            $enrollment = $enrollmentsById->get((int) $enrollmentId);

            if (! $enrollment) {
                continue;
            }

            $gradingService->upsertQuarterGrade(
                $enrollment,
                $subjectAssignment,
                $quarter,
                $this->normalizeScore($row['quiz'] ?? null),
                $this->normalizeScore($row['performance_task'] ?? $row['assignment'] ?? null),
                $this->normalizeScore($row['exam'] ?? null)
            );
        }

        $gradeLevel = (int) ($validated['grade_level'] ?? 0);
        if ($gradeLevel <= 0) {
            $gradeLevel = (int) (Section::query()->whereKey((int) $validated['section_id'])->value('grade_level') ?? 0);
        }

        $subjectCategory = $this->normalizeSubjectCategory((string) $request->input('subject_category', 'core'));

        $redirectUrl = route('gradebook.index', [
            'school_year_id' => $validated['school_year_id'],
            'grade_level' => $gradeLevel,
            'section_id' => $validated['section_id'],
            'subject_id' => $validated['subject_id'],
            'subject_category' => $subjectCategory,
            'quarter' => $quarter,
            'q' => $request->input('q'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Grades auto-saved.',
                'saved_rows' => count($gradeRows),
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)
            ->with('success', 'Grades saved. Subject averages and report cards were updated automatically.');
    }

    private function normalizeScore(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function normalizeSubjectCategory(string $value): string
    {
        $category = strtolower(trim($value));
        $allowed = Subject::CATEGORIES;

        return in_array($category, $allowed, true) ? $category : 'core';
    }
}
