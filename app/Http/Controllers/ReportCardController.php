<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Services\GradingService;
use Illuminate\Http\Request;
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

        foreach ($enrollments as $enrollment) {
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

        return view('report-cards.show', [
            'enrollment' => $enrollment,
            'reportCard' => $enrollment->reportCard,
        ]);
    }
}

