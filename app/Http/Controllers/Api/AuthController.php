<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user->loadMissing('roles');
        if (! $user->hasRole('admin', 'teacher')) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $plainToken = Str::random(80);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $validated['device_name'] ?? 'mobile',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'token' => $plainToken,
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
        $plainToken = trim(substr($header, 7));
        $hash = hash('sha256', $plainToken);

        ApiToken::query()->where('token_hash', $hash)->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
