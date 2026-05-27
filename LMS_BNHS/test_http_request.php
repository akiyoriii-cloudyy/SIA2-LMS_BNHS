<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

echo "Testing HTTP request simulation...\n";

try {
    // Create a request for the logs route
    $request = Request::create('/admin/transactions/logs', 'GET');
    
    echo "Created request for: " . $request->path() . "\n";
    echo "Request method: " . $request->method() . "\n";
    
    // Try to resolve the route
    $route = $app->router->getRoutes()->match($request);
    echo "Route found: " . $route->getName() . "\n";
    
    // Check middleware
    $middleware = $route->middleware();
    echo "Route middleware: " . implode(', ', $middleware) . "\n";
    
    // Get the controller action
    $action = $route->getAction('uses');
    echo "Controller action: " . $action . "\n";
    
    // Check if we can call the action
    if (is_string($action) && str_contains($action, '@')) {
        list($controller, $method) = explode('@', $action);
        echo "Controller: $controller\n";
        echo "Method: $method\n";
        
        // Check if controller exists
        if (class_exists($controller)) {
            echo "Controller class exists: YES\n";
            
            // Check if method exists
            if (method_exists($controller, $method)) {
                echo "Method exists: YES\n";
                
                // Try to instantiate and call the method
                $controllerInstance = app($controller);
                echo "Controller instantiated: YES\n";
                
                // Call the method
                $response = $controllerInstance->$method($request);
                echo "Method called successfully: YES\n";
                echo "Response type: " . get_class($response) . "\n";
                
            } else {
                echo "Method exists: NO\n";
            }
        } else {
            echo "Controller class exists: NO\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\nHTTP request test completed!\n";
