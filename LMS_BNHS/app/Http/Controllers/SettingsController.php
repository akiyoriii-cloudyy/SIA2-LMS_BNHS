<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $request->user()?->loadMissing('profile');

        return view('settings', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'] ?? null,
            $validated['middle_name'] ?? null,
            $validated['last_name'] ?? null,
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
            ]
        );

        return back()->with('status', 'Settings updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        abort(403, 'Password updates for teachers are disabled. Please contact an administrator.');
    }
}
