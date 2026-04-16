<?php

namespace App\Http\Middleware;

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
                $this->sessionTracker->updateSessionActivity($sessionId);

                if (! $this->sessionTracker->isSessionValid($sessionId, $userId)) {
                    auth()->logout();
                    $request->session()->invalidate();

                    return redirect()->route('login')
                        ->withErrors(['email' => 'Your session has been terminated. Please login again.']);
                }
            }
        }

        return $next($request);
    }
}
