<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Strand;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Term;
use App\Services\InAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemManagementController extends Controller
{
    public function __construct(
        private readonly InAppNotificationService $inAppNotifications,
    ) {}

    public function index(): View
    {
        return view('admin.system.index', [
            'currentSchoolYear' => SchoolYear::query()->where('is_active', true)->first(),
            'currentSemester' => Semester::query()->where('is_current', true)->first(),
            'strandsCount' => Strand::query()->count(),
            'subjectsCount' => Subject::query()->count(),
            'termsCount' => Term::query()->count(),
        ]);
    }

    public function academicSettings(): View
    {
        return view('admin.system.academic-settings', [
            'schoolYears' => SchoolYear::query()->orderByDesc('name')->get(),
            'semesters' => Semester::query()->orderBy('name')->get(),
        ]);
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20', 'unique:school_years,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_active'])) {
            SchoolYear::query()->update(['is_active' => false]);
        }

        $name = trim($validated['name']);
        SchoolYear::query()->create([
            'name' => $name,
            'is_active' => ! empty($validated['is_active']),
        ]);

        $msg = ! empty($validated['is_active'])
            ? "You have successfully created academic year «{$name}» and set it as the active year."
            : "You have successfully created academic year «{$name}».";
        $this->inAppNotifications->notifyAllAdmins('academic', 'Academic year', $msg, $this->actorMeta($request));

        return back()->with('status', 'Academic year created.');
    }

    public function setCurrentAcademicYear(Request $request, SchoolYear $schoolYear): RedirectResponse
    {
        SchoolYear::query()->update(['is_active' => false]);
        $schoolYear->update(['is_active' => true]);

        $this->inAppNotifications->notifyAllAdmins(
            'academic',
            'Academic year',
            "The active academic year is now «{$schoolYear->name}».",
            $this->actorMeta($request) + ['school_year_id' => $schoolYear->id],
        );

        return back()->with('status', 'Current academic year updated.');
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:semesters,name'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_current'])) {
            Semester::query()->update(['is_current' => false]);
        }

        $semesterName = trim($validated['name']);
        Semester::query()->create([
            'name' => $semesterName,
            'is_current' => ! empty($validated['is_current']),
        ]);

        $msg = ! empty($validated['is_current'])
            ? "You have successfully created semester «{$semesterName}» and set it as current."
            : "You have successfully created semester «{$semesterName}».";
        $this->inAppNotifications->notifyAllAdmins('academic', 'Semester', $msg, $this->actorMeta($request));

        return back()->with('status', 'Semester created.');
    }

    public function setCurrentSemester(Request $request, Semester $semester): RedirectResponse
    {
        Semester::query()->update(['is_current' => false]);
        $semester->update(['is_current' => true]);

        $this->inAppNotifications->notifyAllAdmins(
            'academic',
            'Semester',
            "The current semester is now «{$semester->name}».",
            $this->actorMeta($request) + ['semester_id' => $semester->id],
        );

        return back()->with('status', 'Current semester updated.');
    }

    public function strands(): View
    {
        return view('admin.system.strands', [
            'strands' => Strand::query()->orderBy('name')->get(),
        ]);
    }

    public function storeStrand(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:strands,code'],
            'name' => ['required', 'string', 'max:100', 'unique:strands,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $strandName = trim($validated['name']);
        Strand::query()->create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => $strandName,
            'description' => $validated['description'] ?? null,
        ]);

        $this->inAppNotifications->notifyAllAdmins(
            'strand',
            'Strand created',
            "A new strand «{$strandName}» was created.",
            $this->actorMeta($request),
        );

        return back()->with('status', 'Strand created.');
    }

    public function updateStrand(Request $request, Strand $strand): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('strands', 'code')->ignore($strand->id)],
            'name' => ['required', 'string', 'max:100', Rule::unique('strands', 'name')->ignore($strand->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $strand->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        $this->inAppNotifications->notifyAllAdmins(
            'strand',
            'Strand updated',
            "Strand «{$strand->name}» was updated.",
            $this->actorMeta($request) + ['strand_id' => $strand->id],
        );

        return back()->with('status', 'Strand updated.');
    }

    public function destroyStrand(Request $request, Strand $strand): RedirectResponse
    {
        $label = $strand->name;
        $strand->delete();

        $this->inAppNotifications->notifyAllAdmins(
            'strand',
            'Strand deleted',
            "Strand «{$label}» was removed from the system.",
            $this->actorMeta($request),
        );

        return back()->with('status', 'Strand deleted.');
    }

    public function terms(): View
    {
        return view('admin.system.terms', [
            'terms' => Term::query()->with(['semester', 'schoolYear'])->orderBy('term_number')->get(),
            'semesters' => Semester::query()->orderBy('name')->get(),
            'schoolYears' => SchoolYear::query()->orderByDesc('name')->get(),
        ]);
    }

    public function storeTerm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'term_number' => ['required', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_active'])) {
            Term::query()->update(['is_active' => false]);
        }

        $termName = trim($validated['name']);
        Term::query()->create([
            'semester_id' => (int) $validated['semester_id'],
            'school_year_id' => (int) $validated['school_year_id'],
            'term_number' => (int) $validated['term_number'],
            'name' => $termName,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => ! empty($validated['is_active']),
        ]);

        $this->inAppNotifications->notifyAllAdmins(
            'term',
            'Term created',
            "A new term «{$termName}» was created.",
            $this->actorMeta($request),
        );

        return back()->with('status', 'Term created.');
    }

    public function updateTerm(Request $request, Term $term): RedirectResponse
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'term_number' => ['required', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_active'])) {
            Term::query()->where('id', '!=', $term->id)->update(['is_active' => false]);
        }

        $term->update([
            'semester_id' => (int) $validated['semester_id'],
            'school_year_id' => (int) $validated['school_year_id'],
            'term_number' => (int) $validated['term_number'],
            'name' => trim($validated['name']),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => ! empty($validated['is_active']),
        ]);

        $this->inAppNotifications->notifyAllAdmins(
            'term',
            'Term updated',
            "Term «{$term->name}» was updated.",
            $this->actorMeta($request) + ['term_id' => $term->id],
        );

        return back()->with('status', 'Term updated.');
    }

    public function destroyTerm(Request $request, Term $term): RedirectResponse
    {
        $label = $term->name;
        $term->delete();

        $this->inAppNotifications->notifyAllAdmins(
            'term',
            'Term deleted',
            "Term «{$label}» was removed.",
            $this->actorMeta($request),
        );

        return back()->with('status', 'Term deleted.');
    }

    public function subjects(): View
    {
        $assignments = TeacherSubject::query()
            ->with(['subject', 'teacher.user'])
            ->orderByDesc('id')
            ->get()
            ->keyBy('subject_id');

        return view('admin.system.subjects', [
            'teachers' => Teacher::query()->with('user')->orderBy('last_name')->get(),
            'subjects' => Subject::query()->orderBy('title')->get(),
            'assignments' => $assignments,
        ]);
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:subjects,code'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(Subject::CATEGORIES)],
        ]);

        $title = trim($validated['title']);
        Subject::query()->create([
            'code' => strtoupper(trim($validated['code'])),
            'title' => $title,
            'category' => $validated['category'] ?? 'core',
        ]);

        $this->inAppNotifications->notifyAllAdmins(
            'subject',
            'Subject created',
            "A new subject «{$title}» was added.",
            $this->actorMeta($request),
        );

        return back()->with('status', 'Subject created.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(Subject::CATEGORIES)],
        ]);

        $subject->update([
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
            'category' => $validated['category'] ?? 'core',
        ]);

        $meta = $this->actorMeta($request) + ['subject_id' => $subject->id];
        $this->inAppNotifications->notifyAllAdmins(
            'subject',
            'Subject updated',
            "Subject «{$subject->title}» ({$subject->code}) was updated.",
            $meta,
        );

        $assignment = TeacherSubject::query()->where('subject_id', $subject->id)->with('teacher.user')->first();
        $assigneeUserId = $assignment?->teacher?->user_id;
        if ($assigneeUserId && $assigneeUserId !== $request->user()->id) {
            $this->inAppNotifications->notifyUser(
                $assigneeUserId,
                'subject',
                'Subject updated',
                "The subject you teach, «{$subject->title}», was updated by an administrator.",
                $meta,
            );
        }

        return back()->with('status', 'Subject updated.');
    }

    public function assignTeacherSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $subjectId = (int) $validated['subject_id'];
        $newTeacherId = (int) $validated['teacher_id'];

        $subject = Subject::query()->findOrFail($subjectId);
        $newTeacher = Teacher::query()->with('user')->findOrFail($newTeacherId);

        $existing = TeacherSubject::query()->where('subject_id', $subjectId)->first();
        $oldTeacherId = $existing?->teacher_id;

        if ($oldTeacherId === $newTeacherId) {
            return back()->with('status', 'Teacher assigned to subject.');
        }

        TeacherSubject::query()->updateOrCreate(
            ['subject_id' => $subjectId],
            ['teacher_id' => $newTeacherId]
        );

        $meta = $this->actorMeta($request) + [
            'subject_id' => $subject->id,
            'teacher_id' => $newTeacher->id,
        ];

        $teacherDisplay = $newTeacher->user
            ? (string) ($newTeacher->user->display_name ?: $newTeacher->user->name)
            : $newTeacher->full_name;

        $this->inAppNotifications->notifyAllAdmins(
            'assignment',
            'Teacher assignment',
            "You have successfully assigned {$teacherDisplay} to teach «{$subject->title}».",
            $meta,
        );

        if ($oldTeacherId !== null) {
            $oldTeacher = Teacher::query()->with('user')->find($oldTeacherId);
            if ($oldTeacher?->user_id) {
                $this->inAppNotifications->notifyUser(
                    $oldTeacher->user_id,
                    'assignment',
                    'Subject assignment removed',
                    "You have been unassigned from «{$subject->title}».",
                    $meta,
                );
            }
        }

        if ($newTeacher->user_id) {
            $this->inAppNotifications->notifyUser(
                $newTeacher->user_id,
                'assignment',
                'New subject assignment',
                "You have been assigned to teach «{$subject->title}».",
                $meta,
            );
        }

        return back()->with('status', 'Teacher assigned to subject.');
    }

    /**
     * @return array{actor_id: int, actor_name: string}
     */
    private function actorMeta(Request $request): array
    {
        $user = $request->user();

        return [
            'actor_id' => $user->id,
            'actor_name' => (string) ($user->display_name ?: $user->name),
        ];
    }
}
