<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "Testing final fixes...\n\n";

// Test 1: Analytics Dashboard
echo "1. Testing Analytics Dashboard:\n";
try {
    $request = Request::create('/admin/transactions/analytics', 'GET');
    $route = $app->router->getRoutes()->match($request);
    
    if ($route) {
        echo "  - Route found: " . $route->getName() . " - SUCCESS\n";
        
        // Check if the clean analytics file exists
        $analyticsPath = __DIR__ . '/resources/views/admin/transactions/analytics.blade.php';
        if (file_exists($analyticsPath)) {
            $content = file_get_contents($analyticsPath);
            
            // Check for clean design elements
            if (str_contains($content, 'stats-grid') && str_contains($content, 'analytics-card')) {
                echo "  - Clean design structure: YES\n";
            } else {
                echo "  - Clean design structure: NO\n";
            }
            
            // Check for simplified layout
            if (str_contains($content, 'page-header') && !str_contains($content, 'analytics-page-header')) {
                echo "  - Simplified header: YES\n";
            } else {
                echo "  - Simplified header: NO\n";
            }
            
            // Check for professional styling
            if (str_contains($content, 'border-radius: 8px') && str_contains($content, 'box-shadow')) {
                echo "  - Professional styling: YES\n";
            } else {
                echo "  - Professional styling: NO\n";
            }
            
            echo "  - Analytics view file: EXISTS\n";
        } else {
            echo "  - Analytics view file: MISSING\n";
        }
    } else {
        echo "  - Route found: NO\n";
    }
} catch (Exception $e) {
    echo "  - Analytics test FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Pagination Styling
echo "2. Testing Pagination Styling:\n";
$logsPath = __DIR__ . '/resources/views/admin/transactions/logs.blade.php';
if (file_exists($logsPath)) {
    $content = file_get_contents($logsPath);
    
    // Check for Tailwind-specific pagination styling
    if (str_contains($content, 'nav[aria-label="Pagination"]')) {
        echo "  - Tailwind pagination targeting: YES\n";
    } else {
        echo "  - Tailwind pagination targeting: NO\n";
    }
    
    // Check for !important declarations
    if (str_contains($content, '!important')) {
        echo "  - CSS priority declarations: YES\n";
    } else {
        echo "  - CSS priority declarations: NO\n";
    }
    
    // Check for hover effects
    if (str_contains($content, 'transform: translateY(-1px)')) {
        echo "  - Advanced hover effects: YES\n";
    } else {
        echo "  - Advanced hover effects: NO\n";
    }
    
    // Check for responsive design
    if (str_contains($content, '@media (max-width: 640px)')) {
        echo "  - Mobile responsive: YES\n";
    } else {
        echo "  - Mobile responsive: NO\n";
    }
    
    echo "  - Logs view file: EXISTS\n";
} else {
    echo "  - Logs view file: MISSING\n";
}

echo "\n";

// Test 3: Route availability
echo "3. Testing Route Availability:\n";
$routesToTest = [
    '/admin/transactions/analytics' => 'Analytics Dashboard',
    '/admin/transactions/logs' => 'Operation Logs',
];

foreach ($routesToTest as $uri => $description) {
    try {
        $request = Request::create($uri, 'GET');
        $route = $app->router->getRoutes()->match($request);
        
        if ($route) {
            echo "  - $description: Available\n";
        } else {
            echo "  - $description: NOT available\n";
        }
    } catch (Exception $e) {
        echo "  - $description: ERROR\n";
    }
}

echo "\n";

// Test 4: File structure check
echo "4. Testing File Structure:\n";
$files = [
    'resources/views/admin/transactions/analytics.blade.php' => 'Analytics View',
    'resources/views/admin/transactions/logs.blade.php' => 'Logs View',
    'resources/views/admin/transactions/analytics_messy.blade.php' => 'Backup (Messy)',
    'resources/views/admin/transactions/logs_broken.blade.php' => 'Backup (Broken)',
];

foreach ($files as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "  - $description: EXISTS\n";
    } else {
        echo "  - $description: MISSING\n";
    }
}

echo "\nTesting completed!\n";
echo "Both fixes should now be working properly.\n";
echo "- Analytics dashboard has been simplified and professionalized\n";
echo "- Pagination styling now targets Tailwind CSS classes with !important\n";
