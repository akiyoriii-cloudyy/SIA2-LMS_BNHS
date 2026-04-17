<?php

namespace App\Services;

use App\Models\UserSession;
use Illuminate\Support\Facades\Request as RequestFacade;

class SessionTracker
{
    public function startSession(int $userId, string $sessionId): UserSession
    {
        $userAgent = RequestFacade::userAgent() ?? 'Unknown';
        $location = $this->getLocationFromIp(RequestFacade::ip());

        return UserSession::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $userId,
                'ip_address' => RequestFacade::ip(),
                'user_agent' => $userAgent,
                'device_type' => $this->getDeviceType($userAgent),
                'browser' => $this->getBrowser($userAgent),
                'os' => $this->getOS($userAgent),
                'location' => $location,
                'started_at' => now(),
                'last_activity_at' => now(),
                'is_active' => true,
                'ended_at' => null,
                'end_reason' => null,
            ]
        );
    }

    public function updateSessionActivity(string $sessionId): void
    {
        UserSession::where('session_id', $sessionId)
            ->where('is_active', true)
            ->update(['last_activity_at' => now()]);
    }

    public function endSession(string $sessionId, string $reason = 'logout'): void
    {
        UserSession::where('session_id', $sessionId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'end_reason' => $reason,
            ]);
    }

    public function endAllUserSessions(int $userId, string $reason = 'admin_terminated'): void
    {
        UserSession::where('user_id', $userId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'end_reason' => $reason,
            ]);
    }

    public function isSessionValid(string $sessionId, int $userId): bool
    {
        return UserSession::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }

    private function getDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        return 'desktop';
    }

    private function getBrowser(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'chrome')) return 'Chrome';
        if (str_contains($ua, 'firefox')) return 'Firefox';
        if (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) return 'Safari';
        if (str_contains($ua, 'edge')) return 'Edge';
        if (str_contains($ua, 'opera')) return 'Opera';
        return 'Unknown';
    }

    private function getOS(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'windows')) return 'Windows';
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) return 'macOS';
        if (str_contains($ua, 'linux')) return 'Linux';
        if (str_contains($ua, 'android')) return 'Android';
        if (str_contains($ua, 'ios') || str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) return 'iOS';
        return 'Unknown';
    }

    private function getLocationFromIp(?string $ip): ?string
    {
        if (! $ip || $ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }

        return null;
    }
}
