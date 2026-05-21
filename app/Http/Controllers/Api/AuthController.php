<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JwtRevokedToken;
use App\Models\User;
use App\Services\JwtService;
use App\Services\PasswordResetMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use PHPMailer\PHPMailer\Exception as MailException;

class AuthController extends Controller
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly PasswordResetMailer $mailer,
    ) {
    }

    private const PASSWORD_RESET_ACK = 'If that email is registered, a password reset link has been sent.';

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

        return response()->json($this->authPayload($user, $token));
    }

    public function rbacProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing(['roles']);

        return response()->json([
            'rbac' => $this->rbacMatrix($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->resolvedPermissionNames(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function authPayload(User $user, string $token): array
    {
        $user->loadMissing(['roles']);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'rbac' => $this->rbacMatrix($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->resolvedPermissionNames(),
            ],
        ];
    }

    /**
     * Role hierarchy and permission catalog for mobile RBAC UI.
     *
     * @return array<string, mixed>
     */
    private function rbacMatrix(User $user): array
    {
        return [
            'hierarchy' => [
                ['role' => 'admin', 'level' => 300, 'label' => 'Administrator', 'description' => 'Full system access'],
                ['role' => 'adviser', 'level' => 200, 'label' => 'Editor (Adviser)', 'description' => 'Modify class records and attendance'],
                ['role' => 'security_guard', 'level' => 150, 'label' => 'User (Security)', 'description' => 'Gate access and limited attendance'],
                ['role' => 'user', 'level' => 100, 'label' => 'User (Limited)', 'description' => 'View-only access'],
            ],
            'your_roles' => $user->roles->pluck('name')->values(),
            'your_permissions' => $user->resolvedPermissionNames(),
        ];
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

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user) {
            return response()->json(['message' => self::PASSWORD_RESET_ACK]);
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        try {
            $this->mailer->send($user, $resetUrl);
        } catch (MailException|\Throwable $e) {
            Log::error('API password reset email failed.', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send the reset link right now. Please try again later.',
            ], 503);
        }

        return response()->json(['message' => self::PASSWORD_RESET_ACK]);
    }
}
