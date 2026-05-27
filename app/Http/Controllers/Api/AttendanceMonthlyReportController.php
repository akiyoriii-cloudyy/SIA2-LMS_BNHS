<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceMonthlyReport;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Services\AttendanceMonthlyReportService;
use App\Services\InAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceMonthlyReportController extends Controller
{
    public function index(Request $request, AttendanceMonthlyReportService $service): JsonResponse
    {
        $user = $request->user();
        if (! $user->teacher) {
            return response()->json(['data' => [], 'web_portal_url' => url('/attendance-reports')]);
        }

        $schoolYearId = $request->integer('school_year_id') ?: null;

        $reports = AttendanceMonthlyReport::query()
            ->with(['section:id,name,grade_level', 'schoolYear:id,name'])
            ->withCount('lines')
            ->withSum('lines as total_absent_days', 'absent_days')
            ->where('teacher_id', $user->teacher->id)
            ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->orderByDesc('report_year')
            ->orderByDesc('report_month')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $reports->map(fn (AttendanceMonthlyReport $r): array => $this->serializeReport($r)),
            'web_portal_url' => url('/attendance-reports'),
        ]);
    }

    public function generate(
        Request $request,
        AttendanceMonthlyReportService $service,
        InAppNotificationService $notifications,
    ): JsonResponse {
        $user = $request->user();
        $teacher = $user->teacher;

        if (! $teacher) {
            return response()->json([
                'message' => 'Your account is not linked to a teacher profile.',
            ], 422);
        }

        $validated = $request->validate([
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'report_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'report_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $schoolYear = isset($validated['school_year_id'])
            ? SchoolYear::query()->findOrFail((int) $validated['school_year_id'])
            : SchoolYear::query()->where('is_active', true)->first();

        if (! $schoolYear) {
            return response()->json([
                'message' => 'No active school year is configured.',
            ], 422);
        }

        $adviserSectionIds = $service->adviserSectionIds($user, (int) $schoolYear->id);

        if ($adviserSectionIds->isEmpty()) {
            return response()->json([
                'message' => 'You are not assigned to any section for this school year.',
            ], 422);
        }

        $targetSectionIds = $adviserSectionIds;

        if (isset($validated['section_id'])) {
            $sectionId = (int) $validated['section_id'];
            if (! $adviserSectionIds->contains($sectionId)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            $targetSectionIds = collect([$sectionId]);
        }

        $generated = [];

        foreach ($targetSectionIds as $sectionId) {
            $section = Section::query()->find((int) $sectionId);
            if (! $section) {
                continue;
            }

            $report = $service->generateOrRefresh(
                $teacher,
                $section,
                $schoolYear,
                (int) $validated['report_year'],
                (int) $validated['report_month'],
                $user,
                true,
            );

            $report->load(['section:id,name,grade_level', 'schoolYear:id,name']);
            $report->loadCount('lines');
            $report->loadSum('lines as total_absent_days', 'absent_days');

            $notifications->notifyUser(
                $user->id,
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

            $generated[] = $this->serializeReport($report);
        }

        if ($generated === []) {
            return response()->json([
                'message' => 'Could not generate a report for the selected period.',
            ], 422);
        }

        $monthLabel = \Carbon\Carbon::create(
            (int) $validated['report_year'],
            (int) $validated['report_month'],
            1,
        )->format('F').' '.(int) $validated['report_year'];

        return response()->json([
            'message' => count($generated) === 1
                ? "Report for {$monthLabel} is ready on the BNHS LMS web portal. Sign in on the web, open Attendance → Monthly Reports, then download the Excel file."
                : count($generated).' reports for '.$monthLabel.' are ready on the BNHS LMS web portal. Open Attendance → Monthly Reports to view and download Excel.',
            'data' => $generated,
            'web_portal_url' => url('/attendance-reports'),
        ]);
    }

    public function show(
        Request $request,
        AttendanceMonthlyReport $attendanceMonthlyReport,
        AttendanceMonthlyReportService $service,
    ): JsonResponse {
        if (! $service->userCanAccess($request->user(), $attendanceMonthlyReport)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $attendanceMonthlyReport->load(['lines', 'section', 'schoolYear']);

        return response()->json([
            'data' => $this->serializeReport($attendanceMonthlyReport, includeLines: true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReport(AttendanceMonthlyReport $report, bool $includeLines = false): array
    {
        $payload = [
            'id' => $report->id,
            'report_year' => $report->report_year,
            'report_month' => $report->report_month,
            'period_label' => $report->periodLabel(),
            'month_name' => $report->monthName(),
            'calendar_year' => $report->calendarYear(),
            'period_range_label' => $report->periodRangeLabel(),
            'status' => $report->status,
            'school_days_total' => $report->school_days_total,
            'notes' => $report->notes,
            'generated_at' => $report->generated_at?->toIso8601String(),
            'emailed_at' => $report->emailed_at?->toIso8601String(),
            'section' => $report->section ? [
                'id' => $report->section->id,
                'name' => $report->section->name,
                'grade_level' => $report->section->grade_level,
            ] : null,
            'school_year' => $report->schoolYear ? [
                'id' => $report->schoolYear->id,
                'name' => $report->schoolYear->name,
            ] : null,
            'lines_count' => $report->lines_count ?? $report->lines->count(),
            'total_absent_days' => (int) ($report->total_absent_days ?? $report->lines->sum('absent_days')),
            'web_url' => $report->webUrl(),
            'print_url' => $report->printUrl(),
            'excel_url' => $report->exportExcelUrl(),
            'reports_index_url' => $report->reportsIndexUrl(),
        ];

        if ($includeLines) {
            $payload['lines'] = $report->lines->map(fn ($line): array => [
                'id' => $line->id,
                'enrollment_id' => $line->enrollment_id,
                'student_name' => $line->student_name,
                'lrn' => $line->lrn,
                'school_days' => $line->school_days,
                'present_days' => $line->present_days,
                'absent_days' => $line->absent_days,
                'late_days' => $line->late_days,
                'excused_days' => $line->excused_days,
                'remarks' => $line->remarks,
            ])->values()->all();
        }

        return $payload;
    }
}
