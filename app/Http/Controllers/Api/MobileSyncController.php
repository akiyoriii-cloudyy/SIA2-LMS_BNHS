<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\SyncBatch;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Services\AttendanceService;
use App\Services\AttendanceMonthlyReportService;
use App\Services\InAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileSyncController extends Controller
{
    public function bootstrap(Request $request, AttendanceMonthlyReportService $monthlyReportService): JsonResponse
    {
        $activeSchoolYear = SchoolYear::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        $sections = Section::query()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'track', 'strand']);

        $assignedSections = collect();
        $user = $request->user();
        if ($user?->teacher && $activeSchoolYear) {
            $assignedSectionIds = $monthlyReportService->adviserSectionIds($user, (int) $activeSchoolYear->id);
            if ($assignedSectionIds->isNotEmpty()) {
                $assignedSections = Section::query()
                    ->whereIn('id', $assignedSectionIds)
                    ->orderBy('grade_level')
                    ->orderBy('name')
                    ->get(['id', 'name', 'grade_level', 'track', 'strand']);
            }
        }

        return response()->json([
            'active_school_year' => $activeSchoolYear ? [
                'id' => $activeSchoolYear->id,
                'name' => $activeSchoolYear->name,
                'is_active' => (bool) $activeSchoolYear->is_active,
            ] : null,
            'sections' => $sections,
            'assigned_sections' => $assignedSections,
            'web_portal' => [
                'base_url' => rtrim((string) config('app.url'), '/'),
                'attendance_reports_url' => url('/attendance-reports'),
                'daily_attendance_url' => url('/attendance'),
            ],
            'features' => [
                'monthly_attendance_reports' => true,
                'mobile_sync_attendance' => true,
            ],
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $schoolYearId = $validated['school_year_id'] ?? SchoolYear::query()->where('is_active', true)->value('id');
        if (! $schoolYearId) {
            return response()->json(['message' => 'No active school year found.'], 422);
        }

        $courses = Course::query()
            ->with(['subject', 'teacher', 'materials', 'assignments'])
            ->where('school_year_id', $schoolYearId)
            ->where('section_id', $validated['section_id'])
            ->where('is_published', true)
            ->get();

        return response()->json(['data' => $courses]);
    }

    public function roster(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $schoolYearId = $validated['school_year_id'] ?? SchoolYear::query()->where('is_active', true)->value('id');
        if (! $schoolYearId) {
            return response()->json(['message' => 'No active school year found.'], 422);
        }

        $enrollments = Enrollment::query()
            ->with(['student.guardians'])
            ->where('school_year_id', $schoolYearId)
            ->where('section_id', $validated['section_id'])
            ->get();

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $enrollmentIds = $enrollments->pluck('id')->values();

        $absencesByEnrollment = AttendanceRecord::query()
            ->select([
                'enrollment_id',
                DB::raw('COUNT(*) as absences_count'),
            ])
            ->whereIn('enrollment_id', $enrollmentIds)
            ->where('school_week_start', $weekStart)
            ->where('status', 'absent')
            ->groupBy('enrollment_id')
            ->pluck('absences_count', 'enrollment_id');

        $smsStatusByEnrollment = SmsLog::query()
            ->where('week_start', $weekStart)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->orderByDesc('id')
            ->get(['enrollment_id', 'status'])
            ->reduce(function (array $carry, SmsLog $log): array {
                if ($log->enrollment_id && ! array_key_exists($log->enrollment_id, $carry)) {
                    $carry[$log->enrollment_id] = $log->status;
                }

                return $carry;
            }, []);

        return response()->json([
            'week_start' => $weekStart,
            'data' => $enrollments->map(function (Enrollment $enrollment) use ($absencesByEnrollment, $smsStatusByEnrollment): array {
                $primaryGuardian = $enrollment->student?->guardians
                    ?->sortByDesc(fn ($guardian): int => (int) $guardian->pivot->is_primary)
                    ?->first();

                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'student_name' => $enrollment->student?->full_name,
                    'lrn' => $enrollment->student?->lrn,
                    'absences_this_week' => (int) ($absencesByEnrollment->get($enrollment->id) ?? 0),
                    'sms_status_this_week' => $smsStatusByEnrollment[$enrollment->id] ?? null,
                    'primary_guardian' => $primaryGuardian ? [
                        'name' => trim($primaryGuardian->first_name.' '.$primaryGuardian->last_name),
                        'phone' => $primaryGuardian->phone,
                        'relationship' => $primaryGuardian->pivot->relationship,
                    ] : null,
                    'guardians' => $enrollment->student?->guardians->map(fn ($guardian): array => [
                        'name' => trim($guardian->first_name.' '.$guardian->last_name),
                        'phone' => $guardian->phone,
                        'relationship' => $guardian->pivot->relationship,
                        'is_primary' => (bool) $guardian->pivot->is_primary,
                    ])->values() ?? [],
                ];
            })->values(),
        ]);
    }

    public function enrollmentProfile(Request $request, Enrollment $enrollment): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => ['nullable', 'date'],
        ]);

        $weekStart = isset($validated['week_start']) && $validated['week_start']
            ? Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString()
            : now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $weekEnd = Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();

        $enrollment->load(['student.guardians', 'section', 'schoolYear']);

        $records = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereBetween('attendance_date', [$weekStart, $weekEnd])
            ->get(['attendance_date', 'status'])
            ->mapWithKeys(fn (AttendanceRecord $r) => [(string) $r->attendance_date => $r->status]);

        $absencesThisWeek = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('school_week_start', $weekStart)
            ->where('status', 'absent')
            ->count();

        $smsStatusThisWeek = SmsLog::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('week_start', $weekStart)
            ->orderByDesc('id')
            ->value('status');

        $primaryGuardian = $enrollment->student?->guardians
            ?->sortByDesc(fn ($guardian): int => (int) $guardian->pivot->is_primary)
            ?->first();

        return response()->json([
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
            ],
            'student' => [
                'id' => $enrollment->student_id,
                'name' => $enrollment->student?->full_name,
                'lrn' => $enrollment->student?->lrn,
                'sex' => $enrollment->student?->sex,
            ],
            'section' => [
                'id' => $enrollment->section_id,
                'name' => $enrollment->section?->name,
                'grade_level' => $enrollment->section?->grade_level,
                'strand' => $enrollment->section?->strand,
            ],
            'school_year' => [
                'id' => $enrollment->school_year_id,
                'name' => $enrollment->schoolYear?->name,
            ],
            'primary_guardian' => $primaryGuardian ? [
                'name' => trim($primaryGuardian->first_name.' '.$primaryGuardian->last_name),
                'phone' => $primaryGuardian->phone,
                'relationship' => $primaryGuardian->pivot->relationship,
            ] : null,
            'guardians' => $enrollment->student?->guardians->map(fn ($guardian): array => [
                'name' => trim($guardian->first_name.' '.$guardian->last_name),
                'phone' => $guardian->phone,
                'relationship' => $guardian->pivot->relationship,
                'is_primary' => (bool) $guardian->pivot->is_primary,
            ])->values() ?? [],
            'attendance_this_week' => $records,
            'absences_this_week' => $absencesThisWeek,
            'sms_status_this_week' => $smsStatusThisWeek,
        ]);
    }

    public function syncAttendance(
        Request $request,
        AttendanceService $attendanceService,
        AttendanceMonthlyReportService $monthlyReportService,
        InAppNotificationService $notifications,
    ): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:120'],
            'batch_uuid' => ['required', 'uuid'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.enrollment_id' => ['required', 'exists:enrollments,id'],
            'records.*.attendance_date' => ['required', 'date'],
            'records.*.status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            'records.*.course_id' => ['nullable', 'exists:courses,id'],
            'records.*.remarks' => ['nullable', 'string'],
        ]);

        $alreadySynced = SyncBatch::query()->where('batch_uuid', $validated['batch_uuid'])->exists();
        if ($alreadySynced) {
            return response()->json(['message' => 'Batch already processed.'], 200);
        }

        $enrollmentIds = collect($validated['records'])->pluck('enrollment_id')->unique()->values();
        $enrollmentMap = Enrollment::query()
            ->whereIn('id', $enrollmentIds)
            ->get(['id', 'section_id', 'school_year_id'])
            ->keyBy('id');

        // Track which section+schoolYear+month needs monthly report regeneration.
        $affectedKeys = [];
        foreach ($validated['records'] as $record) {
            $enrollmentMeta = $enrollmentMap->get((int) $record['enrollment_id']);
            if (! $enrollmentMeta) {
                continue;
            }

            $date = AttendanceMonthlyReportService::parseAttendanceDate($record['attendance_date']);
            $key = implode('|', [
                (int) $enrollmentMeta->section_id,
                (int) $enrollmentMeta->school_year_id,
                (int) $date->year,
                (int) $date->month,
            ]);

            $affectedKeys[$key] = true;
        }

        DB::transaction(function () use ($validated, $request, $attendanceService, $enrollmentMap): void {
            foreach ($validated['records'] as $record) {
                $enrollment = Enrollment::query()->find($record['enrollment_id']);
                if (! $enrollment) {
                    continue;
                }

                $attendanceService->recordAttendance(
                    $enrollment,
                    AttendanceMonthlyReportService::parseAttendanceDate($record['attendance_date']),
                    $record['status'],
                    $record['course_id'] ?? null,
                    $record['remarks'] ?? null,
                    $request->user()
                );
            }

            SyncBatch::create([
                'user_id' => $request->user()->id,
                'device_id' => $validated['device_id'],
                'batch_uuid' => $validated['batch_uuid'],
                'payload' => $validated['records'],
                'status' => 'processed',
                'synced_at' => now(),
            ]);
        });

        $this->regenerateMonthlyReportsForAttendanceChanges(
            array_keys($affectedKeys),
            $request->user(),
            $monthlyReportService,
            $notifications,
        );

        return response()->json(['message' => 'Attendance synced successfully.']);
    }

    public function rfidScan(
        Request $request,
        AttendanceService $attendanceService,
        AttendanceMonthlyReportService $monthlyReportService,
        InAppNotificationService $notifications,
    ): JsonResponse
    {
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:100'],
            'attendance_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['present', 'late', 'absent', 'excused'])],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $schoolYearId = $validated['school_year_id'] ?? SchoolYear::query()
            ->where('is_active', true)
            ->value('id');

        if (! $schoolYearId) {
            return response()->json(['message' => 'No active school year found.'], 422);
        }

        $student = Student::query()
            ->where('rfid_uid', $validated['rfid_uid'])
            ->first();
        if (! $student) {
            return response()->json(['message' => 'RFID card not recognized.'], 404);
        }

        $enrollmentQuery = Enrollment::query()
            ->with(['student.guardians', 'section'])
            ->where('student_id', $student->id)
            ->where('school_year_id', $schoolYearId);

        if (! empty($validated['section_id'])) {
            $enrollmentQuery->where('section_id', (int) $validated['section_id']);
        }

        $enrollment = $enrollmentQuery->orderByDesc('id')->first();
        if (! $enrollment instanceof Enrollment) {
            return response()->json(['message' => 'Student has no active enrollment for the selected school year/section.'], 422);
        }

        $date = isset($validated['attendance_date'])
            ? AttendanceMonthlyReportService::parseAttendanceDate($validated['attendance_date'])
            : now();
        $status = $validated['status'] ?? 'present';

        $record = $attendanceService->recordAttendance(
            $enrollment,
            $date,
            $status,
            isset($validated['course_id']) ? (int) $validated['course_id'] : null,
            $validated['remarks'] ?? null,
            $request->user()
        );

        $key = implode('|', [
            (int) $enrollment->section_id,
            (int) $enrollment->school_year_id,
            (int) $date->year,
            (int) $date->month,
        ]);

        $this->regenerateMonthlyReportsForAttendanceChanges(
            [$key],
            $request->user(),
            $monthlyReportService,
            $notifications,
        );

        $primaryGuardian = $enrollment->student?->guardians
            ?->sortByDesc(fn ($guardian): int => (int) $guardian->pivot->is_primary)
            ?->first();

        return response()->json([
            'message' => 'RFID attendance recorded.',
            'data' => [
                'attendance_record_id' => $record->id,
                'attendance_date' => (string) $record->attendance_date,
                'status' => $record->status,
                'student' => [
                    'id' => $enrollment->student?->id,
                    'name' => $enrollment->student?->full_name,
                    'lrn' => $enrollment->student?->lrn,
                    'rfid_uid' => $enrollment->student?->rfid_uid,
                ],
                'section' => [
                    'id' => $enrollment->section?->id,
                    'name' => $enrollment->section?->name,
                    'grade_level' => $enrollment->section?->grade_level,
                ],
                'primary_guardian' => $primaryGuardian ? [
                    'name' => trim($primaryGuardian->first_name.' '.$primaryGuardian->last_name),
                    'phone' => $primaryGuardian->phone,
                    'relationship' => $primaryGuardian->pivot->relationship,
                ] : null,
            ],
        ]);
    }

    /**
     * Regenerates monthly attendance reports affected by newly synced attendance.
     *
     * @param  list<string>  $affectedKeys  key format: section_id|school_year_id|year|month
     */
    private function regenerateMonthlyReportsForAttendanceChanges(
        array $affectedKeys,
        \App\Models\User $actor,
        AttendanceMonthlyReportService $monthlyReportService,
        InAppNotificationService $notifications,
    ): void {
        if ($affectedKeys === []) {
            return;
        }

        foreach ($affectedKeys as $key) {
            [$sectionId, $schoolYearId, $year, $month] = array_pad(explode('|', $key), 4, null);
            if (! $sectionId || ! $schoolYearId || ! $year || ! $month) {
                continue;
            }

            $section = Section::query()->find((int) $sectionId);
            $schoolYear = SchoolYear::query()->find((int) $schoolYearId);
            if (! $section || ! $schoolYear) {
                continue;
            }

            $teacherIds = SubjectAssignment::query()
                ->where('section_id', (int) $sectionId)
                ->where('school_year_id', (int) $schoolYearId)
                ->whereNotNull('teacher_id')
                ->pluck('teacher_id')
                ->unique();

            if ($teacherIds->isEmpty()) {
                continue;
            }

            foreach ($teacherIds as $teacherId) {
                $teacher = Teacher::query()->with('user')->find((int) $teacherId);
                if (! $teacher?->user) {
                    continue;
                }

                $report = $monthlyReportService->generateOrRefresh(
                    $teacher,
                    $section,
                    $schoolYear,
                    (int) $year,
                    (int) $month,
                    $actor,
                    true,
                );

                $notifications->notifyUser(
                    $teacher->user->id,
                    'attendance_monthly_report',
                    'Monthly attendance report ready',
                    ($section->name ?? 'Section').' — '.$report->monthName().' '.$report->calendarYear().' (Report #'.$report->id.')',
                    [
                        'report_id' => $report->id,
                        'action_url' => $report->webUrl(),
                        'print_url' => $report->printUrl(),
                        'excel_url' => $report->exportExcelUrl(),
                        'reports_index_url' => $report->reportsIndexUrl(),
                    ],
                );
            }
        }
    }
}
