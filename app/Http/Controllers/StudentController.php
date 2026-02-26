<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'active');
        $status = in_array($status, ['active', 'deleted'], true) ? $status : 'active';

        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $sections = Section::query()->orderBy('grade_level')->orderBy('name')->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->firstWhere('is_active', true)?->id ?? $schoolYears->first()?->id ?? 0));
        $selectedSection = (int) ($request->integer('section_id') ?: ($sections->first()?->id ?? 0));
        $search = trim((string) $request->query('q', ''));

        $baseQuery = Enrollment::query()
            ->when($selectedSchoolYear > 0, fn ($q) => $q->where('school_year_id', $selectedSchoolYear))
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

        $query = (clone $baseQuery)
            ->with([
                'student' => fn ($q) => $q->withCount('guardians'),
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
                'guardians' => $guardiansTotal,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'lrn' => ['nullable', 'string', 'max:255', 'unique:students,lrn'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female', 'M', 'F'])],
            'date_of_birth' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $student = Student::query()->create([
            'lrn' => $validated['lrn'] ?? null,
            'first_name' => trim($validated['first_name']),
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => trim($validated['last_name']),
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
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

        return redirect()->route('students.index', [
            'school_year_id' => (int) $validated['school_year_id'],
            'section_id' => (int) $validated['section_id'],
        ])->with('status', 'Student added.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'lrn' => ['nullable', 'string', 'max:255', Rule::unique('students', 'lrn')->ignore($student->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female', 'M', 'F'])],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        $student->update([
            'lrn' => $validated['lrn'] ?? null,
            'first_name' => trim($validated['first_name']),
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => trim($validated['last_name']),
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
        ]);

        return back()->with('status', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return back()->with('status', 'Student deleted.');
    }

    public function restore(string $id): RedirectResponse
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        return back()->with('status', 'Student restored.');
    }
}
