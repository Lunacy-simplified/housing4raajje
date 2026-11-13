<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\SmsChannel;

class OneTimeCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $minutes,
        public string $channel = 'email',
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return $this->channel === 'sms' ? [SmsChannel::class] : ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Housing_4_Raajje sign-in code')
            ->line("Your sign-in code is: {$this->code}")
            ->line("It expires in {$this->minutes} minutes.")
            ->line('If you didn\'t try to sign in, change your password.');
    }

    public function toSms()
    {
        return "Your Housing_4_Raajje verification code is {$this->code}. It expires in {$this->minutes} minutes.";
    }
}
