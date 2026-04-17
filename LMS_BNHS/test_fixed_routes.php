<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "Testing fixed routes...\n\n";

$routesToTest = [
    '/admin/transactions/logs' => 'Transaction Logs',
    '/admin/transactions/analytics' => 'Transaction Analytics',
    '/admin/security-audit/alerts' => 'Security Audit Alerts',
    '/admin/security-audit/reports' => 'Security Audit Reports',
    '/admin/security-audit/export' => 'Security Audit Export',
];

foreach ($routesToTest as $uri => $description) {
    try {
        echo "Testing: $description ($uri)\n";
        
        // Create request
        $request = Request::create($uri, 'GET');
        
        // Try to match route
        $route = $app->router->getRoutes()->match($request);
        
        if ($route) {
            echo "  Route found: " . $route->getName() . "\n";
            echo "  Controller: " . $route->getAction('uses') . "\n";
            echo "  Status: SUCCESS\n";
        } else {
            echo "  Status: FAILED - No route found\n";
        }
        
    } catch (Exception $e) {
        echo "  Status: FAILED - " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Route testing completed!\n";
