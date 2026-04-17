<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking database tables...\n";

try {
    $tables = DB::select('SHOW TABLES');
    echo "Total tables: " . count($tables) . "\n\n";
    
    // Check specific new tables
    $newTables = [
        'user_sessions',
        'activity_logs', 
        'security_audit_logs',
        'security_alerts',
        'business_transactions',
        'transaction_logs'
    ];
    
    foreach ($newTables as $table) {
        $tableName = array_values((array)DB::select("SHOW TABLES LIKE '$table'")[0])[0] ?? null;
        if ($tableName) {
            echo "Table '$table' exists\n";
            
            // Check table structure
            $columns = DB::select("DESCRIBE $table");
            echo "  Columns: " . count($columns) . "\n";
            
            // Check if table has data
            $count = DB::table($table)->count();
            echo "  Records: $count\n";
            
            // Show key columns for normalization check
            echo "  Key columns: ";
            foreach ($columns as $column) {
                if (str_contains($column->Key, 'PRI') || str_contains($column->Key, 'MUL') || str_contains($column->Key, 'UNI')) {
                    echo $column->Field . "({$column->Key}) ";
                }
            }
            echo "\n\n";
        } else {
            echo "Table '$table' MISSING!\n";
        }
    }
    
    // Check all tables
    echo "\nAll tables in database:\n";
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "- $tableName\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDatabase table check completed!\n";
