<?php

/**
 * Check JWT token revocation status for all users
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\JwtRevokedToken;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== JWT Token Revocation Status ===\n\n";

// Count revoked tokens
$revokedCount = JwtRevokedToken::count();
echo "📊 Total Revoked Tokens in Database: {$revokedCount}\n\n";

if ($revokedCount > 0) {
    echo "Revoked Token Details:\n";
    $revoked = JwtRevokedToken::with('user')->get();
    foreach ($revoked as $token) {
        $userName = $token->user ? $token->user->name : 'Unknown';
        $userEmail = $token->user ? $token->user->email : 'N/A';
        echo "  • {$userName} ({$userEmail})\n";
        echo "    JTI: {$token->jti}\n";
        echo "    Revoked: {$token->revoked_at}\n";
        echo "    Expires: {$token->expires_at}\n\n";
    }
}

// Show all API-enabled users
echo "\n👥 All Accounts with JWT API Access (can revoke tokens):\n";
echo str_repeat("-", 60) . "\n";

$users = User::query()
    ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'adviser', 'subject_teacher']))
    ->with('roles')
    ->get();

foreach ($users as $user) {
    $roleName = $user->roles->first()?->name ?? 'unknown';
    $hasMfa = $user->mfa_enabled ? '✅ MFA' : '❌ No MFA';
    
    // Count revoked tokens for this user
    $userRevoked = JwtRevokedToken::where('user_id', $user->id)->count();
    $revokedStatus = $userRevoked > 0 ? "({$userRevoked} tokens revoked)" : "(no revoked tokens)";
    
    echo sprintf(
        "%-25s %-15s %-12s %s\n",
        $user->email,
        "[$roleName]",
        $hasMfa,
        $revokedStatus
    );
}

echo "\n" . str_repeat("-", 60) . "\n";
echo "\n📌 ALL accounts above can use JWT API and revoke tokens.\n";
echo "   When a user logs out via API, their token is added to the revocation list.\n";
