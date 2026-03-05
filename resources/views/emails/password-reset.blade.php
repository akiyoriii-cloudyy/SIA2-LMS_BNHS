<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:#0b1f44;color:#ffffff;">
                            <h1 style="margin:0;font-size:20px;line-height:1.3;">BNHS LMS Password Reset</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px 0;font-size:15px;">Hi {{ $name }},</p>
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;">
                                We received a request to reset your password. Click the button below to continue.
                            </p>
                            <p style="margin:0 0 20px 0;">
                                <a href="{{ $resetUrl }}" style="display:inline-block;background:#0b1f44;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;">Reset Password</a>
                            </p>
                            <p style="margin:0 0 10px 0;font-size:14px;line-height:1.6;">
                                This link will expire in {{ $expiresInMinutes }} minutes.
                            </p>
                            <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;">
                                If the button does not work, copy and paste this URL into your browser:
                            </p>
                            <p style="margin:0 0 16px 0;font-size:13px;line-height:1.6;word-break:break-all;">
                                <a href="{{ $resetUrl }}" style="color:#2563eb;text-decoration:underline;">{{ $resetUrl }}</a>
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">
                                If you did not request a password reset, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
