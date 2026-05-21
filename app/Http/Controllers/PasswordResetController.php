<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordResetMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use PHPMailer\PHPMailer\Exception as MailException;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetMailer $mailer,
    ) {
    }

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'We cannot find a user with that email address.'])
                ->onlyInput('email');
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        try {
            $this->mailer->send($user, $resetUrl);
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email.', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['email' => 'Unable to send the reset link right now. Please try again.'])
                ->onlyInput('email');
        }

        return back()->with('status', 'Password reset link sent. Please check your email.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function ($user, $password): void {
                $user->forceFill(['password' => $password])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
