<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceMonthlyReport;
use App\Services\AttendanceMonthlyReportService;
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
