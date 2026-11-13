<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsChannel
{
    public function send($notifiable, $notification)
    {
        $to = $notifiable->routeNotificationFor('sms', $notification);
        if (! $to) {
            throw new RuntimeException('User has no phone number to send an SMS to.');
        }
        $text = $notification->toSms($notifiable);
        $driver = config('services.sms.driver');

        match ($driver) {
            'log' => Log::info('SMS (log driver)', ['to' => $to, 'body' => $text]),
            'msgowl' => $this->sendWithMsgOwl($to, $text),
            default => throw new RuntimeException("Unknown SMS_DRIVER: {$driver}"),
        };
    }

    private function sendWithMsgOwl($to, $text)
    {
        $response = Http::withHeaders([
            'Authorization' => 'AccessKey '.config('services.msgowl.access_key'),
        ])
            ->acceptJson()
            ->timeout(10)
            ->post('https://rest.msgowl.com/messages', [
                'recipients' => $to,
                'sender_id' => config('services.msgowl.sender_id'),
                'body' => $text,
            ]);

        if ($response->failed()) {
            Log::error('MsgOwl SMS failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('SMS could not be sent.');
        }
    }
}
