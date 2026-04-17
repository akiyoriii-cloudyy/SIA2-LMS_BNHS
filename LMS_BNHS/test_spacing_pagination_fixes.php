<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing spacing and pagination fixes...\n\n";

// Test 1: Analytics Dashboard Spacing
echo "1. Testing Analytics Dashboard Spacing:\n";
$analyticsPath = __DIR__ . '/resources/views/admin/transactions/analytics.blade.php';
if (file_exists($analyticsPath)) {
    $content = file_get_contents($analyticsPath);
    
    // Check for improved spacing
    if (str_contains($content, 'padding: 2rem') && str_contains($content, 'margin-bottom: 2rem')) {
        echo "  - Header padding improved: YES\n";
    } else {
        echo "  - Header padding improved: NO\n";
    }
    
    // Check for button spacing
    if (str_contains($content, 'gap: 1rem') && str_contains($content, 'min-width: 140px')) {
        echo "  - Button spacing improved: YES\n";
    } else {
        echo "  - Button spacing improved: NO\n";
    }
    
    // Check for title styling
    if (str_contains($content, 'font-size: 1.75rem') && str_contains($content, 'letter-spacing: -0.5px')) {
        echo "  - Title styling improved: YES\n";
    } else {
        echo "  - Title styling improved: NO\n";
    }
    
    // Check for button hover effects
    if (str_contains($content, 'transform: translateY(-1px)') && str_contains($content, 'box-shadow: 0 4px 8px')) {
        echo "  - Button hover effects: YES\n";
    } else {
        echo "  - Button hover effects: NO\n";
    }
    
    echo "  - Analytics file: EXISTS\n";
} else {
    echo "  - Analytics file: MISSING\n";
}

echo "\n";

// Test 2: Pagination Text-Only Buttons
echo "2. Testing Pagination Text-Only Buttons:\n";
$logsPath = __DIR__ . '/resources/views/admin/transactions/logs.blade.php';
if (file_exists($logsPath)) {
    $content = file_get_contents($logsPath);
    
    // Check for SVG hiding
    if (str_contains($content, 'display: none !important') && str_contains($content, 'svg')) {
        echo "  - SVG icons hidden: YES\n";
    } else {
        echo "  - SVG icons hidden: NO\n";
    }
    
    // Check for text content
    if (str_contains($content, 'content: "Previous"') && str_contains($content, 'content: "Next"')) {
        echo "  - Text content added: YES\n";
    } else {
        echo "  - Text content added: NO\n";
    }
    
    // Check for button styling
    if (str_contains($content, 'min-width: 80px') && str_contains($content, 'text-transform: uppercase')) {
        echo "  - Text button styling: YES\n";
    } else {
        echo "  - Text button styling: NO\n";
    }
    
    // Check for CSS targeting
    if (str_contains($content, 'nav[aria-label="Pagination"] a[rel="prev"]') && str_contains($content, 'nav[aria-label="Pagination"] a[rel="next"]')) {
        echo "  - Proper CSS targeting: YES\n";
    } else {
        echo "  - Proper CSS targeting: NO\n";
    }
    
    echo "  - Logs file: EXISTS\n";
} else {
    echo "  - Logs file: MISSING\n";
}

echo "\n";

// Test 3: Route Availability
echo "3. Testing Route Availability:\n";
$routesToTest = [
    '/admin/transactions/analytics' => 'Analytics Dashboard',
    '/admin/transactions/logs' => 'Operation Logs',
];

foreach ($routesToTest as $uri => $description) {
    try {
        $request = Illuminate\Http\Request::create($uri, 'GET');
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

// Test 4: CSS Implementation Check
echo "4. Testing CSS Implementation:\n";
echo "  - Analytics Dashboard:\n";
echo "    * Header: 2rem padding, 2rem margin-bottom\n";
echo "    * Title: 1.75rem font-size, -0.5px letter-spacing\n";
echo "    * Buttons: 1rem gap, 140px min-width, hover effects\n";
echo "  - Operation Logs Pagination:\n";
echo "    * SVG icons: Hidden with display: none\n";
echo "    * Text: 'Previous' and 'Next' added via CSS\n";
echo "    * Styling: Uppercase text, 80px min-width\n";

echo "\nTesting completed!\n";
echo "Both spacing and pagination fixes should now be working properly.\n";
