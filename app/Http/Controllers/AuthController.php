<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::query()->where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        if ($user && ! $user->hasPermission('lms.portal')) {
            return back()->withErrors([
                'email' => 'Access denied. Your account does not have portal access. Ask an administrator to grant the LMS portal permission to your role.',
            ])->onlyInput('email');
        }

        if ($user->mfa_enabled && $user->mfa_confirmed_at !== null) {
            $request->session()->put('mfa.pending_user_id', $user->id);
            $request->session()->regenerateToken();
            return redirect()->route('mfa.challenge');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        // Create user session record AFTER session regeneration
        $sessionTracker = app(\App\Services\SessionTracker::class);
        $sessionTracker->startSession($user->id, session()->getId());

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
