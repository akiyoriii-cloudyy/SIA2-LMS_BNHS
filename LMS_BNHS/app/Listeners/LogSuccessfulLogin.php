<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        ActivityLogger::logSuccessfulLogin($event->user->id);
    }
}
