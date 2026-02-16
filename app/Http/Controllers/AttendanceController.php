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
        $sections = Section::query()->orderBy('grade_level')->orderBy('name')->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        $selectedSection = (int) ($request->integer('section_id') ?: ($sections->first()?->id ?? 0));
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
            'attendance_date' => ['required', 'date'],
            'attendance' => ['nullable', 'array'],
            'attendance.*.status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            'attendance.*.remarks' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($validated['attendance_date']);
        foreach ($validated['attendance'] ?? [] as $enrollmentId => $row) {
            $enrollment = Enrollment::query()
                ->where('id', (int) $enrollmentId)
                ->where('school_year_id', $validated['school_year_id'])
                ->where('section_id', $validated['section_id'])
                ->first();

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

        return redirect()->route('attendance.index', [
            'school_year_id' => $validated['school_year_id'],
            'section_id' => $validated['section_id'],
            'attendance_date' => $validated['attendance_date'],
        ])->with('success', 'Attendance saved. Weekly absence alerts are processed automatically.');
    }
}

