<?php

/**
 * Script to check MFA status for all users
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MFA Status Check ===\n\n";

$users = User::query()
    ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'adviser', 'subject_teacher']))
    ->with('roles')
    ->get();

echo "Found " . $users->count() . " users:\n\n";

foreach ($users as $user) {
    $roleName = $user->roles->first()?->name ?? 'unknown';
    $mfaEnabled = $user->mfa_enabled ? 'YES' : 'NO';
    $mfaConfirmed = $user->mfa_confirmed_at ? 'YES (' . $user->mfa_confirmed_at->format('Y-m-d H:i') . ')' : 'NO';
    $mfaSecret = $user->mfa_secret ? 'SET' : 'NOT SET';
    
    echo "👤 {$user->name} ({$user->email})\n";
    echo "   Role: {$roleName}\n";
    echo "   MFA Enabled: {$mfaEnabled}\n";
    echo "   MFA Confirmed: {$mfaConfirmed}\n";
    echo "   MFA Secret: {$mfaSecret}\n";
    
    if ($user->mfa_enabled && $user->mfa_confirmed_at) {
        echo "   ✅ MFA IS ACTIVE - Login should require 2FA code\n";
    } else {
        echo "   ❌ MFA NOT ACTIVE - Login will NOT require 2FA\n";
    }
    echo "\n";
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  To enable MFA:                                            ║\n";
echo "║  1. Login as the user                                      ║\n";
echo "║  2. Go to Settings → Manage MFA                            ║\n";
echo "║  3. Scan QR code with authenticator app                    ║\n";
echo "║  4. Enter 6-digit verification code                      ║\n";
echo "║  5. Save the recovery codes                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
