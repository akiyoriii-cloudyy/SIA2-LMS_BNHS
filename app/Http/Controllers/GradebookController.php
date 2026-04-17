<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\GradingService;
use App\Services\InAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function __construct(
        private readonly InAppNotificationService $inAppNotifications,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $subjectTeacherScoped = $this->isSubjectTeacherOnly($user);
        $adviserScoped = $this->isAdviserOnly($user);
        $teacherScoped = $subjectTeacherScoped || $adviserScoped;

        if ($teacherScoped && ! $user->teacher) {
            return view('gradebook.index', $this->emptyGradebookViewData($request, 'Link your account to a teacher profile to encode grades.'));
        }

        $assignmentScope = $teacherScoped
            ? SubjectAssignment::query()->where('teacher_id', $user->teacher->id)
            : null;

        if ($teacherScoped) {
            $schoolYearIds = (clone $assignmentScope)->distinct()->pluck('school_year_id')->filter()->values();
            $schoolYears = SchoolYear::query()
                ->whereIn('id', $schoolYearIds)
                ->orderByDesc('name')
                ->get();

            if ($schoolYears->isEmpty()) {
                return view('gradebook.index', $this->emptyGradebookViewData($request, 'No subject assignments yet. An administrator must assign you to a section and subject.'));
            }
        } else {
            $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        }

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        if (! $schoolYears->contains('id', $selectedSchoolYear)) {
            $selectedSchoolYear = (int) ($schoolYears->first()?->id ?? 0);
        }

        $assignmentsForYear = $assignmentScope
            ? (clone $assignmentScope)->where('school_year_id', $selectedSchoolYear)->with(['section', 'subject'])->get()
            : null;

        if ($teacherScoped && $assignmentsForYear->isEmpty()) {
            return view('gradebook.index', $this->emptyGradebookViewData($request, 'No assignments for this school year.'));
        }

        if ($assignmentsForYear) {
            $sectionIdsForYear = $assignmentsForYear->pluck('section_id')->unique()->values();
            $gradeLevels = Section::query()
                ->whereIn('id', $sectionIdsForYear)
                ->select('grade_level')
                ->distinct()
                ->orderBy('grade_level')
                ->pluck('grade_level')
                ->map(fn ($level) => (int) $level)
                ->values();
        } else {
            $gradeLevels = Section::query()
                ->select('grade_level')
                ->distinct()
                ->orderBy('grade_level')
                ->pluck('grade_level')
                ->map(fn ($level) => (int) $level)
                ->values();
        }

        $selectedGradeLevel = (int) ($request->integer('grade_level') ?: ((int) ($gradeLevels->first() ?? 0)));
        if (! $gradeLevels->contains($selectedGradeLevel)) {
            $selectedGradeLevel = (int) ($gradeLevels->first() ?? 0);
        }

        $selectedSubjectCategory = $this->normalizeSubjectCategory((string) $request->query('subject_category', 'core'));

        if ($assignmentsForYear) {
            $sectionIdsForLevel = Section::query()
                ->whereIn('id', $sectionIdsForYear)
                ->where('grade_level', $selectedGradeLevel)
                ->pluck('id');
            $sections = Section::query()
                ->whereIn('id', $sectionIdsForLevel)
                ->orderedForDropdown()
                ->get();
        } else {
            $sections = Section::query()
                ->orderedForDropdown()
                ->when($selectedGradeLevel > 0, fn ($q) => $q->where('grade_level', $selectedGradeLevel))
                ->get();
        }

        $requestedSection = (int) $request->integer('section_id');
        $selectedSection = $sections->contains('id', $requestedSection)
            ? $requestedSection
            : (int) ($sections->first()?->id ?? 0);

        if ($assignmentsForYear && $selectedSection) {
            $allowedSubjectIds = $assignmentsForYear
                ->where('section_id', $selectedSection)
                ->pluck('subject_id')
                ->unique()
                ->values();
            $subjectsQuery = Subject::query()
                ->whereIn('id', $allowedSubjectIds)
                ->orderedForDropdown();
            if ($allowedSubjectIds->isNotEmpty()) {
                $subjects = $subjectsQuery->get();
            } else {
                $subjects = collect();
            }
        } else {
            $subjects = Subject::query()
                ->where('category', $selectedSubjectCategory)
                ->orderedForDropdown()
                ->get();
        }

        if ($assignmentsForYear) {
            $subjectCategoryCounts = collect(Subject::CATEGORIES)
                ->mapWithKeys(function (string $category) use ($assignmentsForYear, $selectedSection, $selectedSchoolYear) {
                    $ids = $assignmentsForYear
                        ->where('section_id', $selectedSection)
                        ->pluck('subject_id')
                        ->unique();
                    $count = $ids->isEmpty()
                        ? 0
                        : (int) Subject::query()->whereIn('id', $ids)->where('category', $category)->count();

                    return [$category => $count];
                })
                ->all();
        } else {
            $subjectCategoryCounts = collect(Subject::CATEGORIES)
                ->mapWithKeys(fn (string $category): array => [$category => (int) Subject::query()->where('category', $category)->count()])
                ->all();
        }

        if ($assignmentsForYear && $subjects->isNotEmpty()) {
            $requestedSubject = (int) $request->integer('subject_id');
            $selectedSubject = $subjects->contains('id', $requestedSubject)
                ? $requestedSubject
                : (int) ($subjects->first()?->id ?? 0);
            $selectedSubjectCategory = (string) ($subjects->firstWhere('id', $selectedSubject)?->category ?? $selectedSubjectCategory);
        } else {
            $requestedSubject = (int) $request->integer('subject_id');
            $selectedSubject = $subjects->contains('id', $requestedSubject)
                ? $requestedSubject
                : (int) ($subjects->first()?->id ?? 0);
        }

        $term = $this->resolveTermFromRequest($request);
        $quarter = $term;
        $semester = 1;
        $quarterInSemester = $term;
        $search = trim((string) $request->query('q', ''));

        $subjectAssignment = SubjectAssignment::query()
            ->where('school_year_id', $selectedSchoolYear)
            ->where('section_id', $selectedSection)
            ->where('subject_id', $selectedSubject)
            ->when($teacherScoped, fn ($q) => $q->where('teacher_id', $user->teacher->id))
            ->first();

        $enrollments = collect();
        $existingGrades = collect();
        $sectionEnrollmentIds = collect();
        $rosterNumbers = [];
        $sectionTotalStudents = 0;
        $sectionSubjectsCount = 0;
        $pendingGradesCount = 0;

        if ($selectedSchoolYear && $selectedSection && $subjectAssignment) {
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
            'semester' => $semester,
            'quarterInSemester' => $quarterInSemester,
            'quarter' => $quarter,
            'rosterNumbers' => $rosterNumbers,
            'enrollments' => $enrollments,
            'existingGrades' => $existingGrades,
            'subjectAssignment' => $subjectAssignment,
            'search' => $search,
            'subjectTeacherScoped' => $subjectTeacherScoped,
            'adviserScoped' => $adviserScoped,
            'gradeEntryStats' => [
                'total_students' => $sectionTotalStudents,
                'total_subjects' => $sectionSubjectsCount ?: (int) $subjects->count(),
                'pending' => $pendingGradesCount,
                'school_year' => $selectedSchoolYearModel?->name,
                'section_label' => $selectedSectionModel
                    ? sprintf('Grade %s — %s', $selectedSectionModel->grade_level, $selectedSectionModel->name)
                    : null,
                'subject_title' => $selectedSubjectModel?->title,
                'subject_code' => $selectedSubjectModel?->code,
                'term' => $term,
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
            'quarter' => ['required', 'integer', 'min:1', 'max:3'],
            'grades' => ['nullable', 'array'],
            'grades.*.quiz' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.performance_task' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.assignment' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.exam' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        if ($this->isSubjectTeacherOnly($user)) {
            $subjectAssignment = SubjectAssignment::query()->where([
                'school_year_id' => (int) $validated['school_year_id'],
                'section_id' => (int) $validated['section_id'],
                'subject_id' => (int) $validated['subject_id'],
            ])->first();

            abort_unless(
                $user->teacher
                    && $subjectAssignment
                    && $subjectAssignment->teacher_id === $user->teacher->id,
                403,
                'You may only encode grades for subjects assigned to you.',
            );
        } elseif ($this->isAdviserOnly($user)) {
            $subjectAssignment = SubjectAssignment::query()->where([
                'school_year_id' => (int) $validated['school_year_id'],
                'section_id' => (int) $validated['section_id'],
                'subject_id' => (int) $validated['subject_id'],
            ])->first();

            abort_unless(
                $user->teacher
                    && $subjectAssignment
                    && $subjectAssignment->teacher_id === $user->teacher->id,
                403,
                'You may only encode grades for the subject assigned to you.',
            );
        } else {
            $subjectAssignment = SubjectAssignment::query()->firstOrCreate([
                'school_year_id' => (int) $validated['school_year_id'],
                'section_id' => (int) $validated['section_id'],
                'subject_id' => (int) $validated['subject_id'],
            ]);
        }

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

        if (count($gradeRows) > 0) {
            $this->notifyGradeEncodingChanges(
                $user,
                $subjectAssignment,
                $quarter,
                count($gradeRows),
            );
        }

        $gradeLevel = (int) ($validated['grade_level'] ?? 0);
        if ($gradeLevel <= 0) {
            $gradeLevel = (int) (Section::query()->whereKey((int) $validated['section_id'])->value('grade_level') ?? 0);
        }

        $subjectCategory = $this->normalizeSubjectCategory((string) $request->input('subject_category', 'core'));

        $semester = 1;
        $quarterInSemester = $quarter;

        $redirectUrl = route('gradebook.index', [
            'school_year_id' => $validated['school_year_id'],
            'grade_level' => $gradeLevel,
            'section_id' => $validated['section_id'],
            'subject_id' => $validated['subject_id'],
            'subject_category' => $subjectCategory,
            'semester' => $semester,
            'quarter' => $quarterInSemester,
            'term' => $quarterInSemester,
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

    /**
     * @return array<string, mixed>
     */
    private function emptyGradebookViewData(Request $request, string $message): array
    {
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $gradeLevels = Section::query()->select('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level')->map(fn ($l) => (int) $l)->values();
        $term = $this->resolveTermFromRequest($request);
        $quarter = $term;
        $semester = 1;
        $quarterInSemester = $term;

        return [
            'schoolYears' => $schoolYears,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevel' => 0,
            'sections' => collect(),
            'subjects' => collect(),
            'selectedSchoolYear' => 0,
            'selectedSection' => 0,
            'selectedSubject' => 0,
            'selectedSubjectCategory' => 'core',
            'subjectCategoryCounts' => [],
            'semester' => $semester,
            'quarterInSemester' => $quarterInSemester,
            'quarter' => $quarter,
            'rosterNumbers' => [],
            'enrollments' => collect(),
            'existingGrades' => collect(),
            'subjectAssignment' => null,
            'search' => '',
            'subjectTeacherScoped' => false,
            'gradebookEmptyMessage' => $message,
            'gradeEntryStats' => [
                'total_students' => 0,
                'total_subjects' => 0,
                'pending' => 0,
                'school_year' => null,
                'section_label' => null,
                'subject_title' => null,
                'subject_code' => null,
                'term' => $term,
            ],
        ];
    }

    private function isSubjectTeacherOnly(?User $user): bool
    {
        return $user !== null
            && $user->hasRole('subject_teacher')
            && ! $user->hasRole('admin');
    }

    private function isAdviserOnly(?User $user): bool
    {
        return $user !== null
            && $user->hasRole('adviser')
            && ! $user->hasRole('admin');
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

    private function resolveTermFromRequest(Request $request): int
    {
        $termInput = (int) $request->input('term', 0);
        if ($termInput >= 1 && $termInput <= 3) {
            return $termInput;
        }

        $quarterInput = (int) $request->input('quarter', $request->input('current_quarter', 1));
        return max(1, min(3, $quarterInput));
    }

    private function notifyGradeEncodingChanges(
        User $actor,
        SubjectAssignment $subjectAssignment,
        int $quarter,
        int $savedRows
    ): void {
        $sectionLabel = Section::query()
            ->whereKey($subjectAssignment->section_id)
            ->select(['grade_level', 'name'])
            ->first();
        $subject = Subject::query()->whereKey($subjectAssignment->subject_id)->first(['title', 'code']);
        $schoolYear = SchoolYear::query()->whereKey($subjectAssignment->school_year_id)->value('name');
        $actorName = $actor->display_name ?: $actor->name;
        $quarterLabel = $quarter <= 2 ? "S1 Q{$quarter}" : 'S2 Q'.($quarter - 2);
        $sectionText = $sectionLabel
            ? 'Grade '.$sectionLabel->grade_level.' - '.$sectionLabel->name
            : 'assigned section';
        $subjectText = trim((string) ($subject?->title ?? 'Subject').(($subject?->code ?? null) ? " ({$subject->code})" : ''));
        $message = "{$actorName} saved {$savedRows} grade row(s) for {$subjectText}, {$sectionText}, {$quarterLabel}".($schoolYear ? " ({$schoolYear})" : '').'.';

        $meta = [
            'by_user_id' => $actor->id,
            'subject_assignment_id' => $subjectAssignment->id,
            'school_year_id' => $subjectAssignment->school_year_id,
            'section_id' => $subjectAssignment->section_id,
            'subject_id' => $subjectAssignment->subject_id,
            'quarter' => $quarter,
            'saved_rows' => $savedRows,
        ];

        // Subject teacher updates should alert advisers for section-level report-card finalization.
        if ($this->isSubjectTeacherOnly($actor)) {
            $sectionTeacherIds = SubjectAssignment::query()
                ->where('school_year_id', $subjectAssignment->school_year_id)
                ->where('section_id', $subjectAssignment->section_id)
                ->whereNotNull('teacher_id')
                ->pluck('teacher_id')
                ->unique()
                ->values();

            $adviserUserIds = User::query()
                ->where('id', '!=', $actor->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'adviser'))
                ->whereHas('teacher', fn ($q) => $q->whereIn('id', $sectionTeacherIds))
                ->pluck('id')
                ->values();

            $this->inAppNotifications->notifyUsers(
                $adviserUserIds,
                'grade_sync',
                'Subject grades updated',
                $message,
                $meta
            );

            return;
        }

        // Adviser/admin updates should alert the assigned subject teacher for the encoded subject.
        $subjectTeacherUserIds = User::query()
            ->where('id', '!=', $actor->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'subject_teacher'))
            ->whereHas('teacher', fn ($q) => $q->where('id', $subjectAssignment->teacher_id))
            ->pluck('id')
            ->values();

        $this->inAppNotifications->notifyUsers(
            $subjectTeacherUserIds,
            'grade_sync',
            'Gradebook updated by adviser',
            $message,
            $meta
        );
    }
}
