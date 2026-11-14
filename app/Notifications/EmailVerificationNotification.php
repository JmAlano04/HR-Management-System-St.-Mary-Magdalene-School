<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerificationNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email address - St. Mary Magdalene School')
            ->view([
                'html' => 'emails.verify-email',
                'text' => 'emails.verify-email-text',
            ], [
                'appName' => 'St. Mary Magdalene School',
                'userName' => $notifiable->name,
                'verificationUrl' => $this->verificationUrl($notifiable),
                'logoUrl' => asset('Image/SMMS Logo.png'),
                'expirationMinutes' => config('auth.verification.expire', 60),
            ]);
    }
}
