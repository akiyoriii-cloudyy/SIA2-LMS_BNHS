<?php

namespace App\Http\Middleware;

use App\Models\JwtRevokedToken;
use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwt,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization');
        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('Missing bearer token.');
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return $this->unauthorized('Invalid token.');
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid token.');
        }

        $userId = (int) ($payload['sub'] ?? 0);
        $jti = (string) ($payload['jti'] ?? '');
        if ($userId <= 0) {
            return $this->unauthorized('Invalid token.');
        }

        if ($jti === '') {
            // Token predates revocation support or is malformed.
            return $this->unauthorized('Invalid token.');
        }

        $revoked = JwtRevokedToken::query()->where('jti', $jti)->exists();
        if ($revoked) {
            return $this->unauthorized('Token revoked.');
        }

        $user = User::query()->whereKey($userId)->whereNull('deleted_at')->first();
        if (! $user) {
            return $this->unauthorized('User not found.');
        }

        $request->setUserResolver(fn () => $user);
        Auth::guard('web')->setUser($user);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }
}

