<?php

namespace App\Notifications;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuctionEndingSoonNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Auction $auction)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Auction '.$this->auction->auction_code.' closes soon')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Auction '.$this->auction->auction_code.' ('.$this->auction->item?->name.') closes '.$this->auction->endsAt()->format('M j, Y g:i A').'.')
            ->line('Review your bid before bidding closes.')
            ->action('View auction', route('bidding.index', encrypt($this->auction->id)));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'auction_id' => $this->auction->id,
        ];
    }
}
