<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request as RequestFacade;

class ActivityLogger
{
    public static function log(
        string $action,
        ?string $description = null,
        array|string|null $details = null,
        string $status = 'success',
        ?int $userId = null
    ): ActivityLog {
        $userId ??= auth()->id();

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
            'status' => $status,
            'created_at' => now(),
        ]);
    }

    public static function logWithUser(
        int $userId,
        string $action,
        ?string $description = null,
        array|string|null $details = null,
        string $status = 'success'
    ): ActivityLog {
        return self::log($action, $description, $details, $status, $userId);
    }

    public static function logFailedLogin(string $email, ?string $reason = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => null,
            'action' => 'login.failed',
            'description' => 'Failed login attempt for: ' . $email . ($reason ? ' (' . $reason . ')' : ''),
            'details' => ['email' => $email, 'reason' => $reason],
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
            'status' => 'failed',
            'created_at' => now(),
        ]);
    }

    public static function logSuccessfulLogin(int $userId): ActivityLog
    {
        return self::log('login.success', 'User logged in successfully', null, 'success', $userId);
    }

    public static function logLogout(int $userId): ActivityLog
    {
        return self::log('logout', 'User logged out', null, 'success', $userId);
    }

    public static function logPasswordChange(int $userId, bool $success = true): ActivityLog
    {
        $status = $success ? 'success' : 'failed';
        $description = $success ? 'Password changed successfully' : 'Failed password change attempt';

        return self::log('password.change', $description, null, $status, $userId);
    }

    public static function logProfileUpdate(int $userId, array $changes): ActivityLog
    {
        return self::log('profile.update', 'Profile updated', $changes, 'success', $userId);
    }

    public static function logSuspiciousActivity(
        ?int $userId,
        string $action,
        string $description,
        array $details = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
            'status' => 'warning',
            'created_at' => now(),
        ]);
    }
}
