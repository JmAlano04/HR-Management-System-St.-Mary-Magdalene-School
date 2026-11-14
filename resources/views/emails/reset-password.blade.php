<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Reset your password</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; color:#1f2937; font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f4f6; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px; overflow:hidden; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:12px;">
                    <tr>
                        <td align="center" style="padding:28px 24px; background-color:#b22222;">
                            {{-- <x-application-logo class="w-16 h-16" /> --}}
                            <p style="margin:12px 0 0; color:#ffffff; font-size:18px; font-weight:bold;">{{ $appName }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;">
                            <h1 style="margin:0 0 18px; color:#111827; font-size:24px; line-height:1.3;">Reset your password</h1>
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hello {{ $userName }},</p>
                            <p style="margin:0 0 24px; color:#4b5563; font-size:15px; line-height:1.7;">
                                We received a request to reset the password for your {{ $appName }} account. Use the button below to choose a new password.
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="border-radius:8px; background-color:#b22222;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block; padding:14px 24px; border:1px solid #b22222; border-radius:8px; color:#ffffff; font-size:15px; font-weight:bold; text-decoration:none;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 10px; color:#6b7280; font-size:13px; line-height:1.6;">
                                This password reset link expires in {{ $expirationMinutes }} minutes. If you did not request a password reset, you can safely ignore this email.
                            </p>
                            <p style="margin:0; color:#6b7280; font-size:13px; line-height:1.6;">
                                If the button does not work, copy and paste this link into your browser:<br>
                                <a href="{{ $resetUrl }}" style="color:#b22222; overflow-wrap:anywhere;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 24px; border-top:1px solid #e5e7eb; color:#9ca3af; font-size:12px; line-height:1.5;">
                            {{ $appName }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
