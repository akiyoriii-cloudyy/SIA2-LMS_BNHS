<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UserSession;

echo "Creating sample session data with browser detection...\n";

// Get admin user
$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    echo "No admin user found!\n";
    exit;
}

// Sample user agents for different browsers
$userAgents = [
    'Mozilla Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0',
    'Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Mozilla' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:88.0) Gecko/20100101 Mozilla/5.0',
    'Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
    'Safari' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
];

$ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102', '10.0.0.1', '172.16.0.1'];

// Create sample sessions
foreach ($userAgents as $browserName => $userAgent) {
    for ($i = 0; $i < 3; $i++) {
        $sessionId = 'session_' . uniqid();
        $ip = $ips[array_rand($ips)];
        
        UserSession::create([
            'user_id' => $admin->id,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => 'desktop',
            'browser' => $browserName,
            'os' => 'Windows',
            'location' => 'Local Network',
            'started_at' => now()->subMinutes(rand(10, 1440)),
            'last_activity_at' => now()->subMinutes(rand(5, 60)),
            'is_active' => rand(0, 1) === 1,
            'ended_at' => rand(0, 1) === 0 ? now()->subMinutes(rand(1, 30)) : null,
            'end_reason' => rand(0, 1) === 0 ? 'logout' : null,
        ]);
    }
}

echo "Created sample session data:\n";
echo "- Total sessions: " . UserSession::count() . "\n";
echo "- Active sessions: " . UserSession::where('is_active', true)->count() . "\n";

// Show browser breakdown
echo "\nBrowser breakdown:\n";
$browsers = UserSession::selectRaw('browser, COUNT(*) as count')
    ->groupBy('browser')
    ->orderByDesc('count')
    ->get();

foreach ($browsers as $browser) {
    echo "- {$browser->browser}: {$browser->count} sessions\n";
}

echo "\nSample session data with browser detection completed!\n";
