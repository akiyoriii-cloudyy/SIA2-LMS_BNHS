<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing(['profile', 'roles']);

        return response()->json([
            'data' => $this->serializeProfile($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['suffix'] ?? null,
        ])));

        $user->update([
            'name' => $fullName,
            'phone' => $validated['phone'],
        ]);

        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
            ],
        );

        $user->refresh()->loadMissing(['profile', 'roles']);

        return response()->json([
            'message' => 'Profile updated.',
            'data' => $this->serializeProfile($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(User $user): array
    {
        $profile = $user->profile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'first_name' => $profile?->first_name,
            'middle_name' => $profile?->middle_name,
            'last_name' => $profile?->last_name,
            'suffix' => $profile?->suffix,
            'roles' => $user->roles->pluck('name')->values(),
            'permissions' => $user->resolvedPermissionNames(),
            'web_profile_url' => url('/settings'),
        ];
    }
}
