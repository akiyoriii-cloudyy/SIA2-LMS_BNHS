<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Admin\TransactionController;
use App\Services\TransactionManager;
use Illuminate\Http\Request;

echo "Testing current fixes...\n\n";

// Test 1: Analytics Dashboard Data
echo "1. Testing Analytics Dashboard Data:\n";
try {
    $transactionManager = app(TransactionManager::class);
    $controller = new TransactionController($transactionManager);
    
    // Create mock request
    $request = new Request();
    
    // Call analytics method to get data
    $response = $controller->analytics($request);
    
    // Get the data passed to the view
    $data = $response->getData();
    
    echo "  - Analytics data structure: SUCCESS\n";
    echo "  - Performance metrics available: " . (isset($data['analyticsData']['performance_metrics']) ? 'YES' : 'NO') . "\n";
    
    if (isset($data['analyticsData']['performance_metrics'])) {
        $metrics = $data['analyticsData']['performance_metrics'];
        echo "  - success_rate key exists: " . (array_key_exists('success_rate', $metrics) ? 'YES' : 'NO') . "\n";
        echo "  - success_rate value: " . ($metrics['success_rate'] ?? 'NULL') . "\n";
        echo "  - total_operations: " . ($metrics['total_operations'] ?? 'NULL') . "\n";
        echo "  - failed_operations: " . ($metrics['failed_operations'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "  - Analytics test FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Route availability
echo "2. Testing Route Availability:\n";
$routesToTest = [
    '/admin/transactions/analytics' => 'Transaction Analytics',
    '/admin/transactions/logs' => 'Transaction Logs',
];

foreach ($routesToTest as $uri => $description) {
    try {
        $request = Request::create($uri, 'GET');
        $route = $app->router->getRoutes()->match($request);
        
        if ($route) {
            echo "  - $description: Route found (" . $route->getName() . ")\n";
        } else {
            echo "  - $description: Route NOT found\n";
        }
    } catch (Exception $e) {
        echo "  - $description: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 3: Check pagination styling
echo "3. Testing Pagination Styling:\n";
$logsViewPath = __DIR__ . '/resources/views/admin/transactions/logs.blade.php';
if (file_exists($logsViewPath)) {
    $content = file_get_contents($logsViewPath);
    
    // Check for pagination CSS
    if (str_contains($content, '.pagination')) {
        echo "  - Pagination CSS found: YES\n";
    } else {
        echo "  - Pagination CSS found: NO\n";
    }
    
    // Check for comprehensive pagination classes
    if (str_contains($content, '.page-link') && str_contains($content, '.page-item')) {
        echo "  - Bootstrap pagination classes: YES\n";
    } else {
        echo "  - Bootstrap pagination classes: NO\n";
    }
    
    // Check for !important declarations
    if (str_contains($content, '!important')) {
        echo "  - CSS priority declarations: YES\n";
    } else {
        echo "  - CSS priority declarations: NO\n";
    }
} else {
    echo "  - Logs view file: NOT FOUND\n";
}

echo "\n";

// Test 4: Check analytics view function
echo "4. Testing Analytics View Function:\n";
$analyticsViewPath = __DIR__ . '/resources/views/admin/transactions/analytics.blade.php';
if (file_exists($analyticsViewPath)) {
    $content = file_get_contents($analyticsViewPath);
    
    if (str_contains($content, 'getTransactionTypeIcon')) {
        echo "  - getTransactionTypeIcon function: FOUND\n";
    } else {
        echo "  - getTransactionTypeIcon function: NOT FOUND\n";
    }
    
    if (str_contains($content, '@php')) {
        echo "  - PHP block in view: YES\n";
    } else {
        echo "  - PHP block in view: NO\n";
    }
} else {
    echo "  - Analytics view file: NOT FOUND\n";
}

echo "\nTesting completed!\n";
