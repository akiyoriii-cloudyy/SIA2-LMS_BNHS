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

        if ($request->boolean('refresh')) {
            $actor = $request->user();
            $teacher = $actor?->teacher;
            if ($teacher && (int) $teacher->id === (int) $attendanceMonthlyReport->teacher_id) {
                $service->generateOrRefresh(
                    $teacher,
                    $attendanceMonthlyReport->section,
                    $attendanceMonthlyReport->schoolYear,
                    (int) $attendanceMonthlyReport->report_year,
                    (int) $attendanceMonthlyReport->report_month,
                    $actor,
                    true,
                );
            }
            $attendanceMonthlyReport->refresh();
        }

        $attendanceMonthlyReport->load(['lines', 'section', 'schoolYear', 'teacher.user']);

        return response()->json([
            'data' => $this->serializeReport($attendanceMonthlyReport, includeLines: true),
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
        $shouldSendEmail = $request->boolean('send_email', true);
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
            'message' => $emailMessage ?? 'Attendance records generated. Check web adviser page to download Excel.',
            'data' => $this->serializeReport($report, includeLines: true),
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
