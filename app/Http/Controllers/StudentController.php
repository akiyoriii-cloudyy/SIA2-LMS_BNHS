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

        $enrollments = Enrollment::query()
            ->with([
                'student' => fn ($q) => $q->withCount('guardians'),
                'section',
                'schoolYear',
            ])
            ->when($selectedSchoolYear > 0, fn ($q) => $q->where('school_year_id', $selectedSchoolYear))
            ->when($selectedSection > 0, fn ($q) => $q->where('section_id', $selectedSection))
            ->orderBy('id')
            ->get();

        return view('students.index', [
            'schoolYears' => $schoolYears,
            'sections' => $sections,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'enrollments' => $enrollments,
        ]);
    }
}

