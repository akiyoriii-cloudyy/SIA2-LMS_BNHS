<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
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
            'stats' => [
                'total_enrollments' => $totalEnrollments,
                'total_students' => $totalStudents,
                'male' => $maleCount,
                'female' => $femaleCount,
                'guardians' => $guardiansTotal,
            ],
        ]);
    }
}
