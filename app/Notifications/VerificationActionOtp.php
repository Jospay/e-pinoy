<?php

namespace App\Notifications;

use App\Notifications\Channels\MoviderSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VerificationActionOtp extends Notification
{
    use Queueable;

    public $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [MoviderSmsChannel::class];
    }

    public function toMovider(object $notifiable): string
    {
        return "Your verification code for franchise action is {$this->code}";
    }
}
