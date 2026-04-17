<?php

namespace App\Listeners;

use App\Services\SecurityAuditor;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    private SecurityAuditor $securityAuditor;

    public function __construct(SecurityAuditor $securityAuditor)
    {
        $this->securityAuditor = $securityAuditor;
    }

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'unknown';
        $reason = $event->exception?->getMessage() ?? 'Invalid credentials';
        
        // Log failed login with SecurityAuditor for enhanced security monitoring
        $this->securityAuditor->logFailedLogin($email, $reason);
    }
}
