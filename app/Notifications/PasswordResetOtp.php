<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtp extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $otp,
        private readonly int $expiresInMinutes = 10,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your password reset code')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Use the following one-time password to continue resetting your Agusan National High School account password:')
            ->line("**{$this->otp}**")
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not request a password reset, no further action is required.');

        $from = config('mail.from.address');
        $name = config('mail.from.name');

        if (is_string($from) && $from !== '') {
            $message->from($from, is_string($name) ? $name : null);
        }

        return $message;
    }
}
