<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BusinessTransaction;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Creating sample transaction data...\n";

// Get admin user
$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    echo "No admin user found. Creating one...\n";
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);
}

// Create sample business transactions
$transactionTypes = ['user_creation', 'grade_entry', 'profile_update', 'course_enrollment'];
$statuses = ['committed', 'failed', 'rolled_back'];

for ($i = 0; $i < 10; $i++) {
    $transaction = BusinessTransaction::create([
        'transaction_id' => 'TXN_' . strtoupper(uniqid()),
        'transaction_type' => $transactionTypes[array_rand($transactionTypes)],
        'status' => $statuses[array_rand($statuses)],
        'user_id' => $admin->id,
        'performed_by' => $admin->id,
        'started_at' => now()->subMinutes(rand(1, 1440)),
        'completed_at' => now()->subMinutes(rand(0, 1439)),
        'duration' => rand(100, 5000) / 1000,
        'transaction_data' => [
            'sample_data' => 'Sample transaction data ' . $i,
            'created_for_testing' => true
        ],
        'rollback_data' => rand(0, 1) ? [
            'rollback_data' => 'Sample rollback data ' . $i
        ] : null,
        'description' => 'Sample transaction ' . $i . ' for testing',
    ]);

    // Create transaction logs for each transaction
    $operations = ['insert', 'update', 'delete', 'select'];
    $tables = ['users', 'students', 'subjects', 'grades'];
    
    for ($j = 0; $j < rand(1, 5); $j++) {
        TransactionLog::create([
            'transaction_id' => $transaction->transaction_id,
            'operation' => $operations[array_rand($operations)],
            'table_name' => $tables[array_rand($tables)],
            'record_id' => rand(1, 100),
            'old_values' => rand(0, 1) ? [
                'old_field' => 'old_value_' . $j,
                'another_field' => 'another_old_value'
            ] : null,
            'new_values' => rand(0, 1) ? [
                'new_field' => 'new_value_' . $j,
                'another_field' => 'another_new_value'
            ] : null,
            'sql_query' => 'SELECT * FROM ' . $tables[array_rand($tables)] . ' WHERE id = ?',
            'execution_time_ms' => rand(10, 2000),
            'was_successful' => rand(0, 10) > 2, // 80% success rate
            'error_message' => rand(0, 10) > 8 ? 'Sample error message ' . $j : null,
            'created_at' => $transaction->started_at->addSeconds($j * 2),
        ]);
    }
}

echo "Created " . BusinessTransaction::count() . " business transactions\n";
echo "Created " . TransactionLog::count() . " transaction logs\n";

echo "Sample data creation completed!\n";
