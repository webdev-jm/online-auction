<?php

namespace App\Notifications;

use App\Models\AuctionWinner;
use App\Services\AuctionSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public AuctionWinner $winner)
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
        $settings = app(AuctionSettings::class);
        $auction = $this->winner->auction;

        return (new MailMessage)
            ->subject('Payment reminder for auction '.$auction->auction_code)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your payment of '.number_format((float) $this->winner->bidding->bid_amount, 2).' for auction '.$auction->auction_code.' ('.$auction->item?->name.') is due '.$this->winner->payment_due_at?->format('M j, Y g:i A').'.')
            ->line('Accepted payment: '.$settings->get('payment_methods').'. Pickup is at '.$settings->get('pickup_location').'.')
            ->line('If payment is not received in time, the item may go to the next highest bidder.')
            ->action('View auction', route('bidding.index', encrypt($auction->id)));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'auction_winner_id' => $this->winner->id,
        ];
    }
}
