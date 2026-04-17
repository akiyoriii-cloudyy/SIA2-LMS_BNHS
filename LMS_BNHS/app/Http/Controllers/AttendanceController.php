<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
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

        $sections = Section::query()
            ->orderedForDropdown()
            ->when($selectedGradeLevel > 0, fn ($q) => $q->where('grade_level', $selectedGradeLevel))
            ->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        $requestedSection = (int) $request->integer('section_id');
        $selectedSection = $sections->contains('id', $requestedSection)
            ? $requestedSection
            : (int) ($sections->first()?->id ?? 0);
        $attendanceDate = $request->input('attendance_date', now()->toDateString());

        $enrollments = Enrollment::query()
            ->with('student')
            ->where('school_year_id', $selectedSchoolYear)
            ->where('section_id', $selectedSection)
            ->orderBy('id')
            ->get();

        $records = AttendanceRecord::query()
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('attendance_date', $attendanceDate)
            ->get()
            ->keyBy('enrollment_id');

        return view('attendance.index', [
            'schoolYears' => $schoolYears,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevel' => $selectedGradeLevel,
            'sections' => $sections,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'attendanceDate' => $attendanceDate,
            'enrollments' => $enrollments,
            'records' => $records,
        ]);
    }

    public function store(Request $request, AttendanceService $attendanceService): RedirectResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'grade_level' => ['nullable', 'integer', 'min:1', 'max:12'],
            'attendance_date' => ['required', 'date'],
            'attendance' => ['nullable', 'array'],
            'attendance.*.status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            'attendance.*.remarks' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($validated['attendance_date']);
        $attendanceRows = $validated['attendance'] ?? [];

        $enrollmentsById = Enrollment::query()
            ->where('school_year_id', (int) $validated['school_year_id'])
            ->where('section_id', (int) $validated['section_id'])
            ->whereIn('id', array_map('intval', array_keys($attendanceRows)))
            ->get()
            ->keyBy('id');

        foreach ($attendanceRows as $enrollmentId => $row) {
            /** @var Enrollment|null $enrollment */
            $enrollment = $enrollmentsById->get((int) $enrollmentId);

            if (! $enrollment) {
                continue;
            }

            $attendanceService->recordAttendance(
                $enrollment,
                $date,
                $row['status'],
                null,
                $row['remarks'] ?? null,
                $request->user()
            );
        }

        $gradeLevel = (int) ($validated['grade_level'] ?? 0);
        if ($gradeLevel <= 0) {
            $gradeLevel = (int) (Section::query()->whereKey((int) $validated['section_id'])->value('grade_level') ?? 0);
        }

        return redirect()->route('attendance.index', [
            'school_year_id' => $validated['school_year_id'],
            'grade_level' => $gradeLevel,
            'section_id' => $validated['section_id'],
            'attendance_date' => $validated['attendance_date'],
        ])->with('success', 'Attendance saved. Weekly absence alerts are processed automatically.');
    }
}
