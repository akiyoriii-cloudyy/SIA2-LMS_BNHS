<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use App\Services\SessionTracker;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    private SessionTracker $sessionTracker;

    public function __construct(SessionTracker $sessionTracker)
    {
        $this->sessionTracker = $sessionTracker;
    }

    public function handle(Logout $event): void
    {
        $userId = $event->user->id;
        $sessionId = session()->getId();

        $this->sessionTracker->endSession($sessionId);
        ActivityLogger::logLogout($userId);
    }
}
