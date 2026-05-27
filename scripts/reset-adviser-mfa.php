<?php

/**
 * Script to reset MFA for adviser account
 * This disables MFA so you can login and re-enable it
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Resetting MFA for Adviser Account ===\n\n";

// Find adviser user
$adviser = User::query()
    ->where('email', 'adviser@bnhs.local')
    ->first();

if (! $adviser) {
    echo "❌ Adviser user not found!\n";
    exit(1);
}

echo "👤 Found: {$adviser->name} ({$adviser->email})\n";
echo "   Current MFA Status: " . ($adviser->mfa_enabled ? 'ENABLED' : 'DISABLED') . "\n\n";

// Reset MFA
$adviser->forceFill([
    'mfa_enabled' => false,
    'mfa_secret' => null,
    'mfa_recovery_codes' => null,
    'mfa_confirmed_at' => null,
])->save();

echo "✅ MFA has been RESET for the adviser account!\n\n";

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  You can now login WITHOUT MFA:                            ║\n";
echo "║                                                            ║\n";
echo "║  Email:    adviser@bnhs.local                              ║\n";
echo "║  Password: password123                                     ║\n";
echo "║                                                            ║\n";
echo "║  After logging in:                                         ║\n";
echo "║  1. Go to Settings → Manage MFA                            ║\n";
echo "║  2. Scan the NEW QR code with your iPhone                  ║\n";
echo "║  3. Enter the 6-digit code to verify                       ║\n";
echo "║  4. Save the recovery codes (screenshot them!)             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
