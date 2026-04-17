<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

echo "Checking admin permissions for transactions...\n";

// Get admin user
$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    echo "No admin user found!\n";
    exit;
}

echo "Admin user: {$admin->name} ({$admin->email})\n";

// Check admin role
$adminRole = Role::where('name', 'admin')->first();
if (!$adminRole) {
    echo "No admin role found!\n";
    exit;
}

echo "Admin role ID: {$adminRole->id}\n";

// Check if user has admin role
$userRole = $admin->roles()->where('role_id', $adminRole->id)->first();
if ($userRole) {
    echo "User has admin role\n";
} else {
    echo "User does NOT have admin role - assigning it...\n";
    $admin->roles()->attach($adminRole->id);
}

// Check for transactions.view permission
$transactionPermission = Permission::where('name', 'transactions.view')->first();
if (!$transactionPermission) {
    echo "Creating transactions.view permission...\n";
    $transactionPermission = Permission::create([
        'name' => 'transactions.view',
        'description' => 'View business transactions and logs'
    ]);
}

echo "transactions.view permission ID: {$transactionPermission->id}\n";

// Check if admin role has the permission
$rolePermission = $adminRole->permissions()->where('permission_id', $transactionPermission->id)->first();
if ($rolePermission) {
    echo "Admin role has transactions.view permission\n";
} else {
    echo "Admin role does NOT have transactions.view permission - assigning it...\n";
    $adminRole->permissions()->attach($transactionPermission->id);
}

// Check security_audit.view permission
$securityPermission = Permission::where('name', 'security_audit.view')->first();
if (!$securityPermission) {
    echo "Creating security_audit.view permission...\n";
    $securityPermission = Permission::create([
        'name' => 'security_audit.view',
        'description' => 'View security audit logs and alerts'
    ]);
}

echo "security_audit.view permission ID: {$securityPermission->id}\n";

// Check if admin role has the security permission
$securityRolePermission = $adminRole->permissions()->where('permission_id', $securityPermission->id)->first();
if ($securityRolePermission) {
    echo "Admin role has security_audit.view permission\n";
} else {
    echo "Admin role does NOT have security_audit.view permission - assigning it...\n";
    $adminRole->permissions()->attach($securityPermission->id);
}

echo "\nAll permissions checked and assigned!\n";

// Show all admin permissions
echo "\nAdmin role permissions:\n";
$permissions = $adminRole->permissions()->get();
foreach ($permissions as $permission) {
    echo "- {$permission->name}: {$permission->description}\n";
}

echo "\nPermission check completed!\n";
