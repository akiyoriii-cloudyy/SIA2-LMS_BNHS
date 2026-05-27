<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

echo "Checking transaction routes...\n";

// Get all routes
$routes = Route::getRoutes();

echo "Total routes registered: " . count($routes) . "\n\n";

// Look for transaction routes
$transactionRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    if (str_contains($uri, 'transaction')) {
        $transactionRoutes[] = [
            'uri' => $uri,
            'methods' => implode(', ', $route->methods()),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    }
}

echo "Transaction routes found:\n";
foreach ($transactionRoutes as $route) {
    echo "- {$route['uri']} [{$route['methods']}] -> {$route['action']}\n";
    if ($route['name']) {
        echo "  Name: {$route['name']}\n";
    }
    echo "\n";
}

// Check specifically for logs route
$logsRoute = null;
foreach ($transactionRoutes as $route) {
    if (str_contains($route['uri'], 'logs')) {
        $logsRoute = $route;
        break;
    }
}

if ($logsRoute) {
    echo "Logs route found:\n";
    echo "- URI: {$logsRoute['uri']}\n";
    echo "- Methods: {$logsRoute['methods']}\n";
    echo "- Action: {$logsRoute['action']}\n";
    echo "- Name: {$logsRoute['name']}\n";
} else {
    echo "ERROR: No logs route found!\n";
}

echo "\nRoute checking completed!\n";
