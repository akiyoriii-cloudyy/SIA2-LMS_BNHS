<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use App\Models\SecurityAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Notification;

class SecurityAuditor
{
    private const BRUTE_FORCE_THRESHOLD = 5; // failed attempts in 15 minutes
    private const SUSPICIOUS_IP_THRESHOLD = 10; // failed attempts from same IP
    private const UNUSUAL_ACTIVITY_THRESHOLD = 20; // unusual actions in 1 hour

    public function logFailedLogin(string $email, ?string $reason = null): SecurityAuditLog
    {
        $ip = Request::ip();
        $userAgent = Request::userAgent();

        $auditLog = SecurityAuditLog::create([
            'user_id' => null,
            'event_type' => 'failed_login',
            'severity' => $this->determineSeverity($email, $ip),
            'description' => "Failed login attempt for: {$email}" . ($reason ? " ({$reason})" : ''),
            'details' => [
                'email' => $email,
                'reason' => $reason,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ],
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $this->checkForSecurityBreaches($email, $ip, $auditLog);

        return $auditLog;
    }

    public function logSecurityBreach(
        string $eventType,
        string $description,
        array $details = [],
        string $severity = 'high',
        ?int $userId = null
    ): SecurityAuditLog {
        return SecurityAuditLog::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'details' => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logSuspiciousActivity(
        string $description,
        array $details = [],
        ?int $userId = null,
        string $severity = 'medium'
    ): SecurityAuditLog {
        return SecurityAuditLog::create([
            'user_id' => $userId,
            'event_type' => 'suspicious_activity',
            'severity' => $severity,
            'description' => $description,
            'details' => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function checkForSecurityBreaches(string $email, string $ip, SecurityAuditLog $auditLog): void
    {
        // Check for brute force attacks
        $this->checkBruteForceAttack($email, $ip);

        // Check for suspicious IP activity
        $this->checkSuspiciousIpActivity($ip);

        // Check for account lockout scenarios
        $this->checkAccountLockout($email);

        // Check for unusual activity patterns
        if ($auditLog->user_id) {
            $this->checkUnusualActivity($auditLog->user_id);
        }
    }

    private function checkBruteForceAttack(string $email, string $ip): void
    {
        $recentAttempts = SecurityAuditLog::where('event_type', 'failed_login')
            ->where('details->email', $email)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentAttempts >= self::BRUTE_FORCE_THRESHOLD) {
            $this->createSecurityAlert(
                'brute_force',
                'high',
                'Brute Force Attack Detected',
                "Multiple failed login attempts detected for email: {$email}",
                [
                    'email' => $email,
                    'attempts' => $recentAttempts,
                    'timeframe' => '15 minutes',
                ],
                'email',
                $email
            );
        }
    }

    private function checkSuspiciousIpActivity(string $ip): void
    {
        $recentAttempts = SecurityAuditLog::where('event_type', 'failed_login')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentAttempts >= self::SUSPICIOUS_IP_THRESHOLD) {
            $this->createSecurityAlert(
                'suspicious_ip',
                'high',
                'Suspicious IP Activity',
                "High number of failed login attempts from IP: {$ip}",
                [
                    'ip_address' => $ip,
                    'attempts' => $recentAttempts,
                    'timeframe' => '15 minutes',
                ],
                'ip',
                $ip
            );
        }
    }

    private function checkAccountLockout(string $email): void
    {
        $totalAttempts = SecurityAuditLog::where('event_type', 'failed_login')
            ->where('details->email', $email)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($totalAttempts >= 20) {
            $this->createSecurityAlert(
                'account_lockout',
                'critical',
                'Account Lockout Recommended',
                "Excessive failed login attempts for email: {$email}",
                [
                    'email' => $email,
                    'total_attempts' => $totalAttempts,
                    'timeframe' => '1 hour',
                ],
                'email',
                $email
            );
        }
    }

    private function checkUnusualActivity(int $userId): void
    {
        $recentActivity = SecurityAuditLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentActivity >= self::UNUSUAL_ACTIVITY_THRESHOLD) {
            $user = User::find($userId);
            $this->createSecurityAlert(
                'unusual_activity',
                'medium',
                'Unusual Activity Pattern',
                "Unusual activity pattern detected for user: {$user->name}",
                [
                    'user_id' => $userId,
                    'user_email' => $user->email,
                    'activity_count' => $recentActivity,
                    'timeframe' => '1 hour',
                ],
                'user',
                $userId
            );
        }
    }

    private function createSecurityAlert(
        string $alertType,
        string $severity,
        string $title,
        string $description,
        array $triggerData,
        string $targetType,
        string $targetValue
    ): SecurityAlert {
        // Check if similar alert already exists and is active
        $existingAlert = SecurityAlert::where('alert_type', $alertType)
            ->where('target_type', $targetType)
            ->where('target_value', $targetValue)
            ->where('is_active', true)
            ->first();

        if ($existingAlert) {
            $existingAlert->incrementOccurrence();
            return $existingAlert;
        }

        $alert = SecurityAlert::create([
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
            'trigger_data' => $triggerData,
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'first_occurrence_at' => now(),
            'last_occurrence_at' => now(),
        ]);

        // Send notification to admins
        $this->notifyAdmins($alert);

        return $alert;
    }

    private function notifyAdmins(SecurityAlert $alert): void
    {
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        // Create in-app notifications for admins
        foreach ($adminUsers as $admin) {
            \App\Models\SchoolNotification::create([
                'user_id' => $admin->id,
                'channel' => 'in_app',
                'title' => 'Security Alert: ' . $alert->title,
                'message' => $alert->description,
                'type' => 'security_alert',
                'data' => ['alert_id' => $alert->id],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function determineSeverity(string $email, string $ip): string
    {
        $emailAttempts = Cache::get("failed_login_{$email}", 0);
        $ipAttempts = Cache::get("failed_login_{$ip}", 0);

        if ($emailAttempts >= 10 || $ipAttempts >= 15) {
            return 'critical';
        } elseif ($emailAttempts >= 5 || $ipAttempts >= 8) {
            return 'high';
        } elseif ($emailAttempts >= 3 || $ipAttempts >= 5) {
            return 'medium';
        }

        return 'low';
    }

    public function getSecurityMetrics(): array
    {
        $now = now();
        $last24Hours = $now->copy()->subDay();
        $lastWeek = $now->copy()->subWeek();

        return [
            'failed_logins_24h' => SecurityAuditLog::where('event_type', 'failed_login')
                ->where('created_at', '>=', $last24Hours)->count(),
            'failed_logins_week' => SecurityAuditLog::where('event_type', 'failed_login')
                ->where('created_at', '>=', $lastWeek)->count(),
            'active_alerts' => SecurityAlert::where('is_active', true)->count(),
            'unacknowledged_alerts' => SecurityAlert::where('is_acknowledged', false)
                ->where('is_active', true)->count(),
            'critical_alerts' => SecurityAlert::where('severity', 'critical')
                ->where('is_active', true)->count(),
            'high_severity_alerts' => SecurityAlert::where('severity', 'high')
                ->where('is_active', true)->count(),
        ];
    }
}
