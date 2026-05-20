<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JwtRevokedToken;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly JwtService $jwt,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user->loadMissing(['roles']);
        if (! $user->hasPermission('lms.portal')) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $token = $this->jwt->issueForUser($user, ttlSeconds: 60 * 60 * 24 * 30);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->values(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $header = (string) $request->header('Authorization');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $userId = (int) ($payload['sub'] ?? 0);
        $jti = (string) ($payload['jti'] ?? '');
        $exp = (int) ($payload['exp'] ?? 0);

        if ($userId <= 0 || $jti === '') {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        JwtRevokedToken::query()->firstOrCreate(
            ['jti' => $jti],
            [
                'user_id' => $userId,
                'expires_at' => $exp > 0 ? now()->setTimestamp($exp) : null,
                'revoked_at' => now(),
            ]
        );

        return response()->json(['message' => 'Logged out.']);
    }
}
