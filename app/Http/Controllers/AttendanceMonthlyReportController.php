<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMonthlyReport;
use App\Models\AttendanceMonthlyReportLine;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Services\AttendanceMonthlyReportExcelExporter;
use App\Services\AttendanceMonthlyReportMailer;
use App\Services\AttendanceMonthlyReportService;
use App\Services\InAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PHPMailer\PHPMailer\Exception as MailException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceMonthlyReportController extends Controller
{
    public function index(Request $request, AttendanceMonthlyReportService $service): View
    {
        $user = $request->user();
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->firstWhere('is_active')?->id ?? $schoolYears->first()?->id ?? 0));

        $sectionIds = $service->adviserSectionIds($user, $selectedSchoolYear ?: null);

        $sections = Section::query()
            ->orderedForDropdown()
            ->when($sectionIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $sectionIds))
            ->get();

        $filterMonth = $request->filled('filter_month')
            ? (int) $request->integer('filter_month')
            : null;
        $filterYear = $request->filled('filter_year')
            ? (int) $request->integer('filter_year')
            : null;

        $reports = AttendanceMonthlyReport::query()
            ->with(['section', 'schoolYear'])
            ->withCount('lines')
            ->withSum('lines as total_absent_days', 'absent_days')
            ->when($user->teacher && ! $user->hasRole('admin'), fn ($q) => $q->where('teacher_id', $user->teacher->id))
            ->when($selectedSchoolYear > 0, fn ($q) => $q->where('school_year_id', $selectedSchoolYear))
            ->when($filterMonth, fn ($q) => $q->where('report_month', $filterMonth))
            ->when($filterYear, fn ($q) => $q->where('report_year', $filterYear))
            ->orderByDesc('report_year')
            ->orderByDesc('report_month')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Default to the current calendar month in the school timezone (matches daily attendance).
        $defaultMonth = (int) now()->month;
        $defaultYear = (int) now()->year;

        $currentYear = (int) now()->year;
        $reportYears = range($currentYear - 3, $currentYear + 1);

        $calendarMonths = collect(range(1, 12))->mapWithKeys(
            fn (int $month): array => [
                $month => \Carbon\Carbon::create(2000, $month, 1)->format('F'),
            ],
        );

        return view('attendance-reports.index', [
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $selectedSchoolYear,
            'sections' => $sections,
            'reports' => $reports,
            'defaultMonth' => $defaultMonth,
            'defaultYear' => $defaultYear,
            'calendarMonths' => $calendarMonths,
            'reportYears' => $reportYears,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
        ]);
    }

    public function store(
        Request $request,
        AttendanceMonthlyReportService $service,
        AttendanceMonthlyReportMailer $mailer,
        InAppNotificationService $notifications,
    ): RedirectResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;

        if (! $teacher) {
            return back()->with('error', 'Your account is not linked to a teacher profile.');
        }

        $validated = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'report_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'report_month' => ['required', 'integer', 'min:1', 'max:12'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $sectionIds = $service->adviserSectionIds($user, (int) $validated['school_year_id']);
        if (! $sectionIds->contains((int) $validated['section_id'])) {
            abort(403, 'You are not assigned to this section.');
        }

        $section = Section::query()->findOrFail($validated['section_id']);
        $schoolYear = SchoolYear::query()->findOrFail($validated['school_year_id']);

        $report = $service->generateOrRefresh(
            $teacher,
            $section,
            $schoolYear,
            (int) $validated['report_year'],
            (int) $validated['report_month'],
            $user,
            true,
        );

        $message = 'Monthly attendance report generated from daily attendance (web & mobile). You can edit, print, or email from the report page.';

        if ($request->boolean('send_email')) {
            return $this->deliverReportByEmail($report, $user, $mailer, $notifications, $message.' Email sent.');
        }

        return redirect()
            ->route('attendance-reports.show', $report)
            ->with('success', $message);
    }

    public function show(Request $request, AttendanceMonthlyReport $attendanceMonthlyReport, AttendanceMonthlyReportService $service): View
    {
        $user = request()->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            abort(403);
        }

        $attendanceMonthlyReport->load(['lines.enrollment.student', 'section', 'schoolYear', 'teacher.user']);

        return view('attendance-reports.show', [
            'report' => $attendanceMonthlyReport,
            'openedFromEmail' => $request->query('from') === 'email',
        ]);
    }

    public function print(AttendanceMonthlyReport $attendanceMonthlyReport, AttendanceMonthlyReportService $service): View
    {
        $user = request()->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            abort(403);
        }

        $attendanceMonthlyReport->load(['lines', 'section', 'schoolYear']);

        return view('attendance-reports.print', [
            'report' => $attendanceMonthlyReport,
        ]);
    }

    public function exportExcel(
        Request $request,
        AttendanceMonthlyReport $attendanceMonthlyReport,
        AttendanceMonthlyReportService $service,
        AttendanceMonthlyReportExcelExporter $excelExporter,
    ): \Symfony\Component\HttpFoundation\Response {
        $user = $request->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            abort(403);
        }

        $attendanceMonthlyReport->load(['lines', 'section', 'schoolYear']);

        $spreadsheet = $excelExporter->makeSpreadsheet($attendanceMonthlyReport);

        $period = $attendanceMonthlyReport->periodLabel();
        $safePeriod = preg_replace('/[^A-Za-z0-9]+/', '_', (string) $period) ?: 'report';
        $sectionName = $attendanceMonthlyReport->section?->name ?: 'section';
        $safeSection = preg_replace('/[^A-Za-z0-9]+/', '_', (string) $sectionName) ?: 'section';

        $filename = sprintf(
            'attendance_monthly_report_%s_%s_%d.xlsx',
            $safeSection,
            $safePeriod,
            $attendanceMonthlyReport->id,
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'att_monthly_report_');
        if ($tmpFile === false) {
            abort(500, 'Could not create export file.');
        }

        $xlsxFile = $tmpFile.'.xlsx';
        @unlink($tmpFile); // PhpSpreadsheet will write the real file at $xlsxFile

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return response()
            ->download(
                $xlsxFile,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    public function update(Request $request, AttendanceMonthlyReport $attendanceMonthlyReport, AttendanceMonthlyReportService $service): RedirectResponse
    {
        $user = $request->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'school_days_total' => ['nullable', 'integer', 'min:0', 'max:31'],
            'lines' => ['nullable', 'array'],
            'lines.*.id' => ['required', 'integer', 'exists:attendance_monthly_report_lines,id'],
            'lines.*.school_days' => ['required', 'integer', 'min:0', 'max:31'],
            'lines.*.present_days' => ['required', 'integer', 'min:0', 'max:31'],
            'lines.*.absent_days' => ['required', 'integer', 'min:0', 'max:31'],
            'lines.*.late_days' => ['required', 'integer', 'min:0', 'max:31'],
            'lines.*.excused_days' => ['required', 'integer', 'min:0', 'max:31'],
            'lines.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $attendanceMonthlyReport->update([
            'notes' => $validated['notes'] ?? null,
            'school_days_total' => $validated['school_days_total'] ?? $attendanceMonthlyReport->school_days_total,
        ]);

        $lineIds = $attendanceMonthlyReport->lines()->pluck('id');
        foreach ($validated['lines'] ?? [] as $row) {
            $lineId = (int) $row['id'];
            if (! $lineIds->contains($lineId)) {
                continue;
            }

            AttendanceMonthlyReportLine::query()
                ->whereKey($lineId)
                ->where('attendance_monthly_report_id', $attendanceMonthlyReport->id)
                ->update([
                    'school_days' => (int) $row['school_days'],
                    'present_days' => (int) $row['present_days'],
                    'absent_days' => (int) $row['absent_days'],
                    'late_days' => (int) $row['late_days'],
                    'excused_days' => (int) $row['excused_days'],
                    'remarks' => $row['remarks'] ?? null,
                ]);
        }

        return redirect()
            ->route('attendance-reports.show', $attendanceMonthlyReport)
            ->with('success', 'Report updated. Changes are saved in the system and will appear when you print or resend email.');
    }

    public function refresh(Request $request, AttendanceMonthlyReport $attendanceMonthlyReport, AttendanceMonthlyReportService $service): RedirectResponse
    {
        $user = $request->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            abort(403);
        }

        $teacher = $user->teacher;
        if (! $teacher || (int) $teacher->id !== (int) $attendanceMonthlyReport->teacher_id) {
            abort(403);
        }

        $attendanceMonthlyReport->load(['section', 'schoolYear']);

        $service->generateOrRefresh(
            $teacher,
            $attendanceMonthlyReport->section,
            $attendanceMonthlyReport->schoolYear,
            (int) $attendanceMonthlyReport->report_year,
            (int) $attendanceMonthlyReport->report_month,
            $user,
            true,
        );

        return redirect()
            ->route('attendance-reports.show', $attendanceMonthlyReport)
            ->with('success', 'Report refreshed from daily attendance records.');
    }

    public function sendEmail(
        Request $request,
        AttendanceMonthlyReport $attendanceMonthlyReport,
        AttendanceMonthlyReportService $service,
        AttendanceMonthlyReportMailer $mailer,
        InAppNotificationService $notifications,
    ): RedirectResponse {
        $user = $request->user();
        if (! $service->userCanAccess($user, $attendanceMonthlyReport)) {
            abort(403);
        }

        return $this->deliverReportByEmail(
            $attendanceMonthlyReport,
            $user,
            $mailer,
            $notifications,
            'Monthly attendance report emailed to '.$user->email.'. Use the link in the email or Attendance Reports in your dashboard to view and print.',
        );
    }

    private function deliverReportByEmail(
        AttendanceMonthlyReport $report,
        \App\Models\User $recipient,
        AttendanceMonthlyReportMailer $mailer,
        InAppNotificationService $notifications,
        string $successMessage,
    ): RedirectResponse {
        if (! $recipient->email) {
            return redirect()
                ->route('attendance-reports.show', $report)
                ->with('error', 'Your account has no email address. Update your profile email first.');
        }

        try {
            $mailer->send($recipient, $report);
        } catch (MailException $e) {
            return redirect()
                ->route('attendance-reports.show', $report)
                ->with('error', 'Email could not be sent: '.$e->getMessage());
        }

        $report->update([
            'status' => AttendanceMonthlyReport::STATUS_SENT,
            'emailed_at' => now(),
        ]);

        $report->refresh();
        $webUrl = $report->webUrl().'?from=email';

        $notifications->notifyUser(
            $recipient->id,
            'attendance_monthly_report',
            'Monthly attendance report ready',
            $report->section?->name.' — '.$report->monthName().' '.$report->calendarYear().' (Report #'.$report->id.')',
            [
                'report_id' => $report->id,
                'action_url' => $webUrl,
                'print_url' => $report->printUrl(),
                'excel_url' => $report->exportExcelUrl(),
                'reports_index_url' => $report->reportsIndexUrl(),
            ],
        );

        return redirect()
            ->route('attendance-reports.show', ['attendanceMonthlyReport' => $report, 'from' => 'email'])
            ->with('success', $successMessage);
    }
}
