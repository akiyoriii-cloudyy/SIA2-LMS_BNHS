<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use App\Services\SessionTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    private SessionTracker $sessionTracker;

    public function __construct(SessionTracker $sessionTracker)
    {
        $this->sessionTracker = $sessionTracker;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $sessionId = session()->getId();
            $userId = auth()->id();

            if ($sessionId && $userId) {
                if ($this->sessionTracker->isSessionValid($sessionId, $userId)) {
                    $this->sessionTracker->updateSessionActivity($sessionId);
                } else {
                    // Explicitly ended session (e.g. admin terminated) — force logout.
                    $wasTerminated = UserSession::query()
                        ->where('session_id', $sessionId)
                        ->where('user_id', $userId)
                        ->where('is_active', false)
                        ->exists();

                    if ($wasTerminated) {
                        auth()->logout();
                        $request->session()->invalidate();

                        return redirect()->route('login')
                            ->withErrors(['email' => 'Your session has been terminated. Please login again.']);
                    }

                    // Missing tracker row (e.g. MFA login before SessionTracker registration).
                    $this->sessionTracker->startSession((int) $userId, $sessionId);
                }
            }
        }

        return $next($request);
    }
}
