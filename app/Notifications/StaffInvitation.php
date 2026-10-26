<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('staff-invite.show', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Welcome to Housing4Raajje — Set Your Password')
            ->greeting('Welcome to Housing4Raajje!')
            ->line('Your staff account has been created.')
            ->line('Set a password to activate your account.')
            ->action('Set your password', $url)
            ->line('This invitation link expires in 24 hours.')
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
