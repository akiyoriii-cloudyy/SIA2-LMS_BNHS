<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Admin\TransactionController;
use App\Services\TransactionManager;
use Illuminate\Http\Request;

echo "Testing TransactionController logs method...\n";

try {
    // Create the controller with its dependency
    $transactionManager = app(TransactionManager::class);
    $controller = new TransactionController($transactionManager);
    
    // Create a mock request
    $request = new Request();
    
    echo "Calling logs method...\n";
    
    // Call the logs method
    $response = $controller->logs($request);
    
    echo "Logs method executed successfully!\n";
    echo "Response type: " . get_class($response) . "\n";
    
    // Check if it's a View response
    if (method_exists($response, 'getName')) {
        echo "View name: " . $response->getName() . "\n";
    }
    
    // Check view data
    if (method_exists($response, 'getData')) {
        $data = $response->getData();
        echo "View data keys: " . implode(', ', array_keys($data)) . "\n";
        
        if (isset($data['logs'])) {
            echo "Logs count: " . $data['logs']->count() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR in logs method: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nController test completed!\n";
