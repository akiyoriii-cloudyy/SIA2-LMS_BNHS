<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class PasswordResetController extends Controller
{
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
            $this->sendPasswordResetEmail($user, $resetUrl);
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

    private function sendPasswordResetEmail(User $user, string $resetUrl): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $config = config('services.phpmailer');
        $username = (string) ($config['username'] ?? '');
        $password = str_replace(' ', '', (string) ($config['password'] ?? ''));

        if ($username === '' || $password === '') {
            throw new MailException('PHPMailer SMTP credentials are not configured.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) ($config['host'] ?? 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Port = (int) ($config['port'] ?? 587);

        $encryption = strtolower((string) ($config['encryption'] ?? 'tls'));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $fromAddress = (string) ($config['from_address'] ?? $username);
        $fromName = (string) ($config['from_name'] ?? config('app.name', 'BNHS LMS'));

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($user->email, $user->name);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Link - '.config('app.name', 'BNHS LMS');

        $passwordBroker = (string) config('auth.defaults.passwords', 'users');
        $expiresInMinutes = (int) config("auth.passwords.{$passwordBroker}.expire", 60);

        $mail->Body = view('emails.password-reset', [
            'name' => $user->name,
            'resetUrl' => $resetUrl,
            'expiresInMinutes' => $expiresInMinutes,
        ])->render();

        $mail->AltBody = "Hi {$user->name},\n\nUse this link to reset your password:\n{$resetUrl}\n\n"
            ."This link expires in {$expiresInMinutes} minutes.\n\n"
            ."If you did not request this, you can ignore this email.";

        $mail->send();
    }
}
