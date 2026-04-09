<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Services\InAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly InAppNotificationService $inAppNotifications,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'active');
        $status = in_array($status, ['active', 'deleted'], true) ? $status : 'active';

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

        $sections = Section::query()
            ->orderedForDropdown()
            ->when($selectedGradeLevel > 0, fn ($q) => $q->where('grade_level', $selectedGradeLevel))
            ->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->firstWhere('is_active', true)?->id ?? $schoolYears->first()?->id ?? 0));
        $requestedSection = (int) $request->integer('section_id');
        $selectedSection = $sections->contains('id', $requestedSection)
            ? $requestedSection
            : (int) ($sections->first()?->id ?? 0);
        $search = trim((string) $request->query('q', ''));

        $baseQuery = Enrollment::query()
            ->when($selectedSchoolYear > 0, fn ($q) => $q->where('school_year_id', $selectedSchoolYear))
            ->when(
                $selectedGradeLevel > 0,
                fn ($q) => $q->whereHas('section', fn ($sq) => $sq->where('grade_level', $selectedGradeLevel))
            )
            ->when($selectedSection > 0, fn ($q) => $q->where('section_id', $selectedSection));

        $totalEnrollments = (clone $baseQuery)->count();
        $totalStudents = (clone $baseQuery)->distinct('student_id')->count('student_id');
        $activeCount = (clone $baseQuery)->whereHas('student', fn ($q) => $q->whereNull('deleted_at'))->distinct('student_id')->count('student_id');
        $deletedCount = (clone $baseQuery)->whereHas('student', fn ($q) => $q->whereNotNull('deleted_at'))->distinct('student_id')->count('student_id');

        $maleCount = (clone $baseQuery)
            ->whereHas('student', fn ($q) => $q->whereIn('sex', ['M', 'm', 'Male', 'male']))
            ->distinct('student_id')
            ->count('student_id');

        $femaleCount = (clone $baseQuery)
            ->whereHas('student', fn ($q) => $q->whereIn('sex', ['F', 'f', 'Female', 'female']))
            ->distinct('student_id')
            ->count('student_id');

        $blaanCount = (clone $baseQuery)
            ->whereHas('student', fn ($q) => $q->whereRaw("LOWER(TRIM(COALESCE(ethnicity, ''))) = ?", ['blaan']))
            ->distinct('student_id')
            ->count('student_id');

        $islamCount = (clone $baseQuery)
            ->whereHas('student', fn ($q) => $q->whereRaw("LOWER(TRIM(COALESCE(ethnicity, ''))) in (?, ?)", ['islam', 'muslim']))
            ->distinct('student_id')
            ->count('student_id');

        $query = (clone $baseQuery)
            ->with([
                'student' => fn ($q) => $q
                    ->withCount('guardians')
                    ->with([
                        'guardians' => fn ($g) => $g->select('guardians.id', 'first_name', 'last_name', 'phone'),
                    ]),
                'section',
                'schoolYear',
            ])
            ->when($status === 'active', fn ($q) => $q->whereHas('student', fn ($s) => $s->whereNull('deleted_at')))
            ->when($status === 'deleted', fn ($q) => $q->whereHas('student', fn ($s) => $s->whereNotNull('deleted_at')))
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('student', function ($s) use ($search): void {
                    $s->where('lrn', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->get();

        $guardiansTotal = (int) $query->sum(fn ($e) => (int) ($e->student?->guardians_count ?? 0));

        return view('students.index', [
            'schoolYears' => $schoolYears,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevel' => $selectedGradeLevel,
            'sections' => $sections,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'enrollments' => $query,
            'search' => $search,
            'status' => $status,
            'activeCount' => $activeCount,
            'deletedCount' => $deletedCount,
            'stats' => [
                'total_enrollments' => $totalEnrollments,
                'total_students' => $totalStudents,
                'male' => $maleCount,
                'female' => $femaleCount,
                'blaan' => $blaanCount,
                'islam' => $islamCount,
                'guardians' => $guardiansTotal,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'grade_level' => ['nullable', 'integer', 'min:1', 'max:12'],
            'lrn' => ['nullable', 'string', 'max:255', 'unique:students,lrn'],
            'rfid_uid' => ['nullable', 'string', 'max:100', 'unique:students,rfid_uid'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female', 'M', 'F'])],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $student = Student::query()->create([
            'lrn' => $validated['lrn'] ?? null,
            'rfid_uid' => $validated['rfid_uid'] ?? null,
            'first_name' => trim($validated['first_name']),
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => trim($validated['last_name']),
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'age' => $validated['age'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'address' => $validated['address'] ?? null,
            'ethnicity' => $validated['ethnicity'] ?? null,
        ]);

        Enrollment::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'school_year_id' => (int) $validated['school_year_id'],
            ],
            [
                'section_id' => (int) $validated['section_id'],
                'status' => (string) ($validated['status'] ?? 'active'),
            ],
        );

        $gradeLevel = (int) ($validated['grade_level'] ?? 0);
        if ($gradeLevel <= 0) {
            $gradeLevel = (int) (Section::query()->whereKey((int) $validated['section_id'])->value('grade_level') ?? 0);
        }

        $student->refresh();
        $meta = $this->actorMeta($request) + ['student_id' => $student->id];
        $line = "Student «{$student->full_name}» was added to records.";
        $this->inAppNotifications->notifyAllAdmins('student_record', 'Student added', $line, $meta);
        $this->inAppNotifications->notifyAllTeachers('student_record', 'Student added', $line, $meta);

        return redirect()->route('students.index', [
            'school_year_id' => (int) $validated['school_year_id'],
            'grade_level' => $gradeLevel,
            'section_id' => (int) $validated['section_id'],
        ])->with('status', 'Student added.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'lrn' => ['nullable', 'string', 'max:255', Rule::unique('students', 'lrn')->ignore($student->id)],
            'rfid_uid' => ['nullable', 'string', 'max:100', Rule::unique('students', 'rfid_uid')->ignore($student->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female', 'M', 'F'])],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
        ]);

        $student->update([
            'lrn' => $validated['lrn'] ?? null,
            'rfid_uid' => $validated['rfid_uid'] ?? null,
            'first_name' => trim($validated['first_name']),
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => trim($validated['last_name']),
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'age' => $validated['age'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'address' => $validated['address'] ?? null,
            'ethnicity' => $validated['ethnicity'] ?? null,
        ]);

        $student->refresh();
        $ethnicityLabel = $student->ethnicity ?: 'not set';
        $addressLabel = $student->address ?: 'not set';

        $meta = $this->actorMeta($request) + ['student_id' => $student->id];
        $line = "Student «{$student->full_name}» was updated.";
        $this->inAppNotifications->notifyAllAdmins('student_record', 'Student updated', $line, $meta);
        $this->inAppNotifications->notifyAllTeachers('student_record', 'Student updated', $line, $meta);

        return back()->with('status', "Student updated. Ethnicity: {$ethnicityLabel}; Address: {$addressLabel}.");
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $label = $student->full_name;
        $meta = $this->actorMeta($request) + ['student_id' => $student->id];
        $student->delete();

        $line = "Student «{$label}» was removed from active records.";
        $this->inAppNotifications->notifyAllAdmins('student_record', 'Student deleted', $line, $meta);
        $this->inAppNotifications->notifyAllTeachers('student_record', 'Student deleted', $line, $meta);

        return back()->with('status', 'Student deleted.');
    }

    public function restore(Request $request, string $id): RedirectResponse
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        $meta = $this->actorMeta($request) + ['student_id' => $student->id];
        $line = "Student «{$student->full_name}» was restored.";
        $this->inAppNotifications->notifyAllAdmins('student_record', 'Student restored', $line, $meta);
        $this->inAppNotifications->notifyAllTeachers('student_record', 'Student restored', $line, $meta);

        return back()->with('status', 'Student restored.');
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
