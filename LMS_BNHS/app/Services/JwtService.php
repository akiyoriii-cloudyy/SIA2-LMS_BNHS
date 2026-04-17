<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;

class JwtService
{
    public function issueForUser(User $user, int $ttlSeconds = 2592000): string
    {
        $now = time();
        $payload = [
            'iss' => Config::get('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + max(60, $ttlSeconds),
            'sub' => (int) $user->id,
            'jti' => $this->newJti(),
            'roles' => $user->roles()->pluck('name')->values()->all(),
        ];

        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret(), 'HS256'));

        return json_decode(json_encode($decoded), true) ?: [];
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret', '');
        if ($secret !== '') {
            return $secret;
        }

        // Fallback to app key material (still deterministic per app).
        return hash('sha256', (string) config('app.key'));
    }

    private function newJti(): string
    {
        return bin2hex(random_bytes(16));
    }
}

