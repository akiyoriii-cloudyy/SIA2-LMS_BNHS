<?php

namespace App\Services;

use App\Models\AttendanceMonthlyReport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class AttendanceMonthlyReportMailer
{
    public function send(User $recipient, AttendanceMonthlyReport $report): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $config = config('services.phpmailer');
        $username = (string) ($config['username'] ?? '');
        $password = str_replace(' ', '', (string) ($config['password'] ?? ''));

        if ($username === '' || $password === '') {
            throw new MailException('PHPMailer SMTP credentials are not configured. Set PHPM_MAIL_* in .env.');
        }

        $report->refresh();
        $report->load(['lines', 'section', 'schoolYear', 'teacher.user']);
        $reportUrl = $report->webUrl();
        $printUrl = $report->printUrl();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) ($config['host'] ?? 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Port = (int) ($config['port'] ?? 587);

        $encryption = strtolower((string) ($config['encryption'] ?? 'tls'));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $fromAddress = (string) ($config['from_address'] ?? $username);
        $fromName = (string) ($config['from_name'] ?? config('app.name', 'BNHS LMS'));

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipient->email, $recipient->name);
        $mail->isHTML(true);

        $period = $report->periodLabel();
        $sectionName = $report->section?->name ?? 'Section';
        $schoolYear = $report->schoolYear?->name ?? '';

        $mail->Subject = "Monthly Attendance Report — {$sectionName} ({$period})";

        $mail->Body = view('emails.attendance-monthly-report', [
            'name' => $recipient->name,
            'report' => $report,
            'reportUrl' => $reportUrl,
            'printUrl' => $printUrl,
            'period' => $period,
            'sectionName' => $sectionName,
            'schoolYear' => $schoolYear,
        ])->render();

        $absentTotal = (int) $report->lines->sum('absent_days');
        $mail->AltBody = "Hi {$recipient->name},\n\n"
            ."Your monthly attendance report for {$sectionName} ({$period}) is ready.\n"
            ."School year: {$schoolYear}\n"
            ."Report ID: #{$report->id}\n"
            ."Total student absences recorded: {$absentTotal}\n\n"
            ."View & edit in BNHS LMS (adviser dashboard):\n{$reportUrl}\n\n"
            ."Print-ready page:\n{$printUrl}\n\n"
            ."Daily attendance from the web and mobile app is synced to this report.";

        try {
            $mail->send();
        } catch (MailException $e) {
            Log::error('Attendance monthly report email failed', [
                'report_id' => $report->id,
                'recipient' => $recipient->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
