<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SessionTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    public function challenge(Request $request): View
    {
        $pendingUserId = (int) $request->session()->get('mfa.pending_user_id', 0);
        abort_unless($pendingUserId > 0, 403);

        return view('auth.mfa-challenge', [
            'email' => (string) (User::query()->whereKey($pendingUserId)->value('email') ?? ''),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pendingUserId = (int) $request->session()->get('mfa.pending_user_id', 0);
        abort_unless($pendingUserId > 0, 403);

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::query()->whereKey($pendingUserId)->firstOrFail();
        abort_unless($user->mfa_enabled && $user->mfa_confirmed_at !== null, 403);

        $raw = strtoupper(trim((string) $validated['code']));
        $raw = preg_replace('/\s+/', '', $raw) ?? $raw;

        $ok = false;
        if (preg_match('/^\d{6}$/', $raw)) {
            $g2fa = new Google2FA;
            $ok = $user->mfa_secret ? $g2fa->verifyKey((string) $user->mfa_secret, $raw) : false;
        } else {
            // Recovery code flow.
            $codes = (array) ($user->mfa_recovery_codes ?? []);
            foreach ($codes as $idx => $hash) {
                if (is_string($hash) && Hash::check($raw, $hash)) {
                    $ok = true;
                    unset($codes[$idx]); // one-time use
                    $user->mfa_recovery_codes = array_values($codes);
                    $user->save();
                    break;
                }
            }
        }

        if (! $ok) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        Auth::loginUsingId($user->id, true);
        $request->session()->forget('mfa.pending_user_id');
        $request->session()->regenerate();

        app(SessionTracker::class)->startSession((int) $user->id, session()->getId());

        return redirect()->route('dashboard');
    }

    public function securityHome(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        return view('security', [
            'user' => $user,
        ]);
    }

    public function setup(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        $g2fa = new Google2FA;
        $secret = $g2fa->generateSecretKey();
        $issuer = 'BNHS LMS';
        $label = (string) $user->email;
        $otpauth = $g2fa->getQRCodeUrl($issuer, $label, $secret);

        return view('settings-mfa', [
            'user' => $user,
            'secret' => $secret,
            'otpauth' => $otpauth,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'secret' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $g2fa = new Google2FA;
        $code = preg_replace('/\D+/', '', trim((string) $validated['code'])) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return back()->withErrors(['code' => 'Invalid authenticator code.'])->withInput();
        }

        $secret = preg_replace('/\s+/', '', trim((string) $validated['secret'])) ?? (string) $validated['secret'];
        $ok = $g2fa->verifyKey($secret, $code, 4);

        if (! $ok) {
            return back()->withErrors(['code' => 'Invalid authenticator code.'])->withInput();
        }

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $plain = strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
            $recoveryCodes[] = Hash::make($plain);
            $plainList[] = $plain;
        }

        $user->forceFill([
            'mfa_enabled' => true,
            'mfa_secret' => (string) $secret,
            'mfa_recovery_codes' => $recoveryCodes,
            'mfa_confirmed_at' => now(),
        ])->save();

        $request->session()->flash('recovery_codes', $plainList ?? []);

        return redirect()->route('security')->with('status', 'MFA enabled. Save your recovery codes.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'mfa_confirmed_at' => null,
        ])->save();

        return redirect()->route('security')->with('status', 'MFA disabled.');
    }
}
