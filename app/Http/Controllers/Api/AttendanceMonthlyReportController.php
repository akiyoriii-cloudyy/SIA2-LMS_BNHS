<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceMonthlyReport;
use App\Services\AttendanceMonthlyReportMailer;
use App\Services\AttendanceMonthlyReportService;
use App\Services\InAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\Exception as MailException;

class AttendanceMonthlyReportController extends Controller
{
    public function index(Request $request, AttendanceMonthlyReportService $service): JsonResponse
    {
        $user = $request->user();
        if (! $user->teacher) {
            return response()->json(['data' => [], 'web_portal_url' => url('/attendance-reports')]);
        }

        $schoolYearId = $request->integer('school_year_id') ?: null;
        $service->ensureCurrentMonthReports($request->user(), $schoolYearId);

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
            'data' => $reports->map(fn (AttendanceMonthlyReport $r): array => $this->serializeReport($r, $service)),
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

        $attendanceMonthlyReport->load(['lines', 'section', 'schoolYear', 'teacher.user']);

        return response()->json([
            'data' => $this->serializeReport($attendanceMonthlyReport, $service, includeLines: true),
        ]);
    }

    public function generate(
        Request $request,
        AttendanceMonthlyReport $attendanceMonthlyReport,
        AttendanceMonthlyReportService $service,
        AttendanceMonthlyReportMailer $mailer,
        InAppNotificationService $notifications,
    ): JsonResponse {
        $user = $request->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $teacher = $user->teacher;
        if (! $teacher || (int) $teacher->id !== (int) $attendanceMonthlyReport->teacher_id) {
            return response()->json(['message' => 'Only the adviser owner can generate this report.'], 403);
        }

        $attendanceMonthlyReport->load(['section', 'schoolYear']);

        $report = $service->generateOrRefresh(
            $teacher,
            $attendanceMonthlyReport->section,
            $attendanceMonthlyReport->schoolYear,
            (int) $attendanceMonthlyReport->report_year,
            (int) $attendanceMonthlyReport->report_month,
            $user,
            true,
        );

        $emailMessage = null;
        $shouldSendEmail = $request->boolean('send_email', false);
        if ($shouldSendEmail && ! empty($user->email)) {
            try {
                $mailer->send($user, $report);
                $report->update([
                    'status' => AttendanceMonthlyReport::STATUS_SENT,
                    'emailed_at' => now(),
                ]);
                $emailMessage = 'Report generated and emailed to '.$user->email.'.';
            } catch (MailException $e) {
                $emailMessage = 'Report generated, but email failed: '.$e->getMessage();
            }
        }

        $report->refresh();
        $report->load(['lines', 'section', 'schoolYear']);

        $notifications->notifyUser(
            $user->id,
            'attendance_monthly_report',
            'Monthly attendance report ready',
            ($report->section?->name ?? 'Section').' — '.$report->periodLabel().' (Report #'.$report->id.')',
            [
                'report_id' => $report->id,
                'action_url' => $report->webUrl(),
                'print_url' => $report->printUrl(),
            ],
        );

        return response()->json([
            'message' => $emailMessage ?? 'Report generated. Open the adviser page on the web to view, print, or download.',
            'data' => $this->serializeReport($report, $service, includeLines: true),
            'web_portal_url' => $report->webUrl(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReport(
        AttendanceMonthlyReport $report,
        AttendanceMonthlyReportService $service,
        bool $includeLines = false,
    ): array {
        $payload = [
            'id' => $report->id,
            'report_year' => $report->report_year,
            'report_month' => $report->report_month,
            'period_label' => $report->periodLabel(),
            'status' => $report->status,
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
            'total_absent_days' => $service->liveTotalAbsentDays($report),
            'school_days_total' => $service->liveMonthSchoolDaysTotal($report),
            'needs_generate' => ! $service->reportHasGeneratedTotals($report),
            'web_url' => $report->webUrl(),
            'print_url' => $report->printUrl(),
        ];

        if ($includeLines) {
            $liveSummaries = $service->liveSummariesByEnrollmentId($report);
            $attendanceByEnrollment = $service->attendanceByEnrollmentForReportMonth($report);

            $payload['lines'] = $report->lines->map(function ($line) use ($service, $liveSummaries, $attendanceByEnrollment): array {
                $enrollmentId = (int) $line->enrollment_id;

                return $service->serializeLineForDisplay(
                    $line,
                    $liveSummaries[$enrollmentId] ?? [],
                    $attendanceByEnrollment[$enrollmentId] ?? [],
                );
            })->values()->all();
        }

        return $payload;
    }
}
