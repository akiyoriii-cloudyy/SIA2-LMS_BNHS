<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class PasswordResetMailer
{
    public function send(User $user, string $resetUrl): void
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
