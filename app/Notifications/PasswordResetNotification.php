<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your password - St. Mary Magdalene School')
            ->view([
                'html' => 'emails.reset-password',
                'text' => 'emails.reset-password-text',
            ], [
                'appName' => 'St. Mary Magdalene School',
                'userName' => $notifiable->name,
                'resetUrl' => $this->resetUrl($notifiable),
                'logoUrl' => asset('Image/SMMS Logo.png'),
                'expirationMinutes' => config(
                    'auth.passwords.'.config('auth.defaults.passwords').'.expire',
                    60
                ),
            ]);
    }
}
