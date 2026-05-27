<?php

/**
 * Generate and revoke a JWT token for admin to show in database
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\JwtRevokedToken;
use App\Services\JwtService;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Generating Revoked Token for Admin ===\n\n";

// Find admin
$admin = User::query()->where('email', 'admin@bnhs.local')->first();

if (! $admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "👤 Admin: {$admin->name} (ID: {$admin->id})\n\n";

// Create JWT service and issue token
$jwt = app(JwtService::class);
$token = $jwt->issueForUser($admin, ttlSeconds: 60 * 60 * 24 * 30);

echo "✅ Generated JWT Token:\n";
echo "   " . substr($token, 0, 50) . "...\n\n";

// Decode to get JTI
$payload = $jwt->decode($token);
$jti = $payload['jti'] ?? '';
$exp = $payload['exp'] ?? 0;

echo "📋 Token Details:\n";
echo "   JTI: {$jti}\n";
echo "   User ID: {$admin->id}\n";
echo "   Expires: " . date('Y-m-d H:i:s', $exp) . "\n\n";

// Revoke the token (store in database)
$revoked = JwtRevokedToken::query()->firstOrCreate(
    ['jti' => $jti],
    [
        'user_id' => $admin->id,
        'expires_at' => $exp > 0 ? now()->setTimestamp($exp) : null,
        'revoked_at' => now(),
    ]
);

echo "✅ Token Revoked and Stored in Database!\n";
echo "   Record ID: {$revoked->id}\n";
echo "   Revoked At: {$revoked->revoked_at}\n\n";

// Show all revoked tokens now
$allRevoked = JwtRevokedToken::with('user')->get();
echo "📊 All Revoked Tokens in Database (" . $allRevoked->count() . " total):\n";
echo str_repeat("-", 70) . "\n";
echo sprintf("%-5s %-25s %-20s %-20s\n", "ID", "User", "JTI", "Revoked At");
echo str_repeat("-", 70) . "\n";

foreach ($allRevoked as $rt) {
    $userName = $rt->user ? $rt->user->email : 'Unknown';
    echo sprintf(
        "%-5d %-25s %-20s %-20s\n",
        $rt->id,
        substr($userName, 0, 24),
        substr($rt->jti, 0, 18) . "...",
        $rt->revoked_at->format('Y-m-d H:i:s')
    );
}

echo str_repeat("-", 70) . "\n";
echo "\n✅ Admin now has a revoked token in the database!\n";
echo "   Refresh phpMyAdmin to see the new entry.\n";
