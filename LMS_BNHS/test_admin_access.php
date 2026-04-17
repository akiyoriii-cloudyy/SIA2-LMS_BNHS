<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

echo "Testing admin user access...\n";

// Get admin user
$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    echo "ERROR: No admin user found!\n";
    exit;
}

echo "Admin user found: {$admin->name} (ID: {$admin->id})\n";

// Check if user has admin role
$adminRole = Role::where('name', 'admin')->first();
if (!$adminRole) {
    echo "ERROR: No admin role found!\n";
    exit;
}

echo "Admin role found: {$adminRole->name} (ID: {$adminRole->id})\n";

// Check user-role relationship
$userHasAdminRole = $admin->roles()->where('role_id', $adminRole->id)->exists();
echo "User has admin role: " . ($userHasAdminRole ? 'YES' : 'NO') . "\n";

if (!$userHasAdminRole) {
    echo "Assigning admin role to user...\n";
    $admin->roles()->attach($adminRole->id);
    echo "Admin role assigned.\n";
}

// Check transactions.view permission
$transactionPermission = Permission::where('name', 'transactions.view')->first();
if (!$transactionPermission) {
    echo "ERROR: transactions.view permission not found!\n";
    exit;
}

echo "transactions.view permission found: ID {$transactionPermission->id}\n";

// Check if admin role has the permission
$roleHasPermission = $adminRole->permissions()->where('permission_id', $transactionPermission->id)->exists();
echo "Admin role has transactions.view permission: " . ($roleHasPermission ? 'YES' : 'NO') . "\n";

if (!$roleHasPermission) {
    echo "Assigning permission to admin role...\n";
    $adminRole->permissions()->attach($transactionPermission->id);
    echo "Permission assigned.\n";
}

// Test user can method
$canViewTransactions = $admin->hasPermission('transactions.view');
echo "User can view transactions: " . ($canViewTransactions ? 'YES' : 'NO') . "\n";

// Test role check
$hasAdminRole = $admin->hasRole('admin');
echo "User has admin role: " . ($hasAdminRole ? 'YES' : 'NO') . "\n";

// Check if user can access admin routes
$canAccessAdmin = $admin->hasRole('admin');
echo "User can access admin routes: " . ($canAccessAdmin ? 'YES' : 'NO') . "\n";

echo "\nAuthorization test completed!\n";

// Now let's test the middleware
echo "\nTesting middleware simulation...\n";

// Simulate the middleware check
$middlewarePasses = false;
if ($admin->hasRole('admin') && $admin->hasPermission('transactions.view')) {
    $middlewarePasses = true;
}

echo "Middleware would pass: " . ($middlewarePasses ? 'YES' : 'NO') . "\n";

if (!$middlewarePasses) {
    echo "ERROR: Middleware check failed!\n";
    echo "Missing requirements:\n";
    if (!$admin->hasRole('admin')) {
        echo "- Admin role\n";
    }
    if (!$admin->hasPermission('transactions.view')) {
        echo "- transactions.view permission\n";
    }
} else {
    echo "All middleware requirements met!\n";
}
