<?php

namespace App\Console\Commands;

use App\Models\AttendanceMonthlyReport;
use App\Models\SchoolYear;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Services\AttendanceMonthlyReportMailer;
use App\Services\AttendanceMonthlyReportService;
use App\Services\InAppNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PHPMailer\PHPMailer\Exception as MailException;

class SendMonthlyAttendanceReportsCommand extends Command
{
    protected $signature = 'attendance:send-monthly-reports
                            {--year= : Report year (defaults to previous calendar month)}
                            {--month= : Report month 1-12 (defaults to previous calendar month)}
                            {--school-year= : School year ID (defaults to active)}
                            {--send : Actually email advisers (otherwise only generates drafts)}';

    protected $description = 'Generate monthly section attendance reports for advisers and optionally email them';

    public function handle(
        AttendanceMonthlyReportService $service,
        AttendanceMonthlyReportMailer $mailer,
        InAppNotificationService $notifications,
    ): int {
        $previous = now()->subMonth();
        $year = (int) ($this->option('year') ?: $previous->year);
        $month = (int) ($this->option('month') ?: $previous->month);

        $schoolYear = SchoolYear::query()
            ->when($this->option('school-year'), fn ($q) => $q->whereKey((int) $this->option('school-year')))
            ->when(! $this->option('school-year'), fn ($q) => $q->where('is_active', true))
            ->orderByDesc('id')
            ->first();

        if (! $schoolYear) {
            $this->error('No school year found. Pass --school-year=ID or mark a year as active.');

            return self::FAILURE;
        }

        $period = Carbon::create($year, $month, 1)->format('F Y');
        $this->info("Processing monthly attendance reports for {$period} (school year: {$schoolYear->name})");

        $teacherIds = SubjectAssignment::query()
            ->where('school_year_id', $schoolYear->id)
            ->whereNotNull('teacher_id')
            ->pluck('teacher_id')
            ->unique();

        $generated = 0;
        $emailed = 0;
        $failed = 0;

        foreach ($teacherIds as $teacherId) {
            $teacher = Teacher::query()->with('user')->find($teacherId);
            if (! $teacher?->user) {
                continue;
            }

            $sectionIds = SubjectAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $schoolYear->id)
                ->pluck('section_id')
                ->unique();

            foreach ($sectionIds as $sectionId) {
                $section = \App\Models\Section::query()->find($sectionId);
                if (! $section) {
                    continue;
                }

                $report = $service->generateOrRefresh(
                    $teacher,
                    $section,
                    $schoolYear,
                    $year,
                    $month,
                    $teacher->user,
                    true,
                );
                $generated++;

                if (! $this->option('send')) {
                    continue;
                }

                try {
                    $mailer->send($teacher->user, $report);
                    $report->update([
                        'status' => AttendanceMonthlyReport::STATUS_SENT,
                        'emailed_at' => now(),
                    ]);
                    $report->refresh();
                    $notifications->notifyUser(
                        $teacher->user->id,
                        'attendance_monthly_report',
                        'Monthly attendance report ready',
                        ($section->name ?? 'Section').' — '.$report->periodLabel().' (Report #'.$report->id.')',
                        [
                            'report_id' => $report->id,
                            'action_url' => $report->webUrl().'?from=email',
                            'print_url' => $report->printUrl(),
                        ],
                    );
                    $emailed++;
                } catch (MailException $e) {
                    $failed++;
                    $this->warn("Email failed for {$teacher->user->email} / {$section->name}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Generated/updated {$generated} report(s).");
        if ($this->option('send')) {
            $this->info("Emailed {$emailed} report(s). Failed: {$failed}.");
        } else {
            $this->comment('Run with --send to email advisers (requires PHPM_MAIL_* in .env).');
        }

        return self::SUCCESS;
    }
}
