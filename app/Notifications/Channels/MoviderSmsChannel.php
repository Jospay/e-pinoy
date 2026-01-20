<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class MoviderSmsChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toMovider')) {
            return;
        }

        if (!$notifiable->phone) {
            return;
        }

        Http::asForm()->post('https://api.movider.co/v1/sms', [
            'api_key'    => config('services.movider.key'),
            'api_secret' => config('services.movider.secret'),
            'to'         => $notifiable->phone,
            'from'       => config('services.movider.sender'),
            'text'       => $notification->toMovider($notifiable),
        ]);
    }
}
