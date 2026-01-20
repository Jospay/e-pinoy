<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\MoviderSmsChannel;

class AcceptFranchiseApplication extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', MoviderSmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Franchise Application Approval')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('We are pleased to inform you that your franchise application has been successfully reviewed and approved.')
            ->line('You are now officially part of our franchise network, and we look forward to supporting your journey with us.')
            ->action('Access Your Dashboard', url('/dashboard'))
            ->line('Thank you for your commitment and trust. We are excited to work with you and wish you every success.');
    }

    public function toMovider(object $notifiable): string
    {
        return "Hi {$notifiable->name}, we are pleased to inform you that your franchise application has been successfully reviewed and approved. You may now access your dashboard.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
