<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The one general-purpose alert: saved in-app (notifications table) and emailed.
 * Send with Notice::to($user, 'Title', 'What happened.', route('...')).
 */
class Notice extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
    ) {}

    /** Send to one user or many. A failed email must never break the action that caused it. */
    public static function to($users, string $title, string $body, ?string $url = null): void
    {
        foreach (collect($users instanceof \Illuminate\Database\Eloquent\Model ? [$users] : $users)->filter()->unique('id') as $user) {
            try {
                $user->notify(new self($title, $body, $url));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->body);

        return $this->url ? $mail->action('Open', $this->url) : $mail;
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
