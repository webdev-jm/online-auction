<?php

namespace App\Notifications;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutbidNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Auction $auction, public float $highestBid)
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
            ->subject('You have been outbid on '.$this->auction->auction_code)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Someone placed a higher bid on auction '.$this->auction->auction_code.' ('.$this->auction->item?->name.').')
            ->line('The highest bid is now '.number_format($this->highestBid, 2).'. Bidding closes '.$this->auction->endsAt()->format('M j, Y g:i A').'.')
            ->action('Bid again', route('bidding.index', encrypt($this->auction->id)));
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
            'highest_bid' => $this->highestBid,
        ];
    }
}
