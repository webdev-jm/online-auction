<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Models\Bidding;
use App\Models\User;
use App\Notifications\AuctionEndingSoonNotification;
use App\Notifications\AuctionWinnerNotification;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuctionLifecycle
{
    private const THROTTLE_KEY = 'auctions-sync-throttle';

    public function __construct(private AuctionSettings $settings)
    {
    }

    /**
     * Run sync() at most once a minute. Pages call this so statuses stay right on servers without cron.
     */
    public function syncIfDue(): void
    {
        if (Cache::add(self::THROTTLE_KEY, true, 60)) {
            $this->sync();
        }
    }

    /**
     * Bring every auction up to date.
     *
     * @return array{statuses: int, winners: int, ending_soon: int, reminders: int, forfeited: int}
     */
    public function sync(): array
    {
        return [
            'statuses' => $this->syncStatuses(),
            'winners' => $this->selectPendingWinners(),
            'ending_soon' => $this->sendEndingSoonNotices(),
            'reminders' => $this->sendPaymentReminders(),
            'forfeited' => $this->forfeitOverdueWinners(),
        ];
    }

    public function syncStatuses(): int
    {
        $changed = 0;

        Auction::query()->each(function (Auction $auction) use (&$changed): void {
            $status = $auction->currentStatus();
            if ($auction->status !== $status) {
                $auction->update(['status' => $status]);
                $changed++;
            }
        });

        return $changed;
    }

    /**
     * Pick a winner for every ended auction that has bids and no winner yet.
     */
    public function selectPendingWinners(): int
    {
        $selected = 0;

        Auction::query()
            ->where('status', Auction::STATUS_ENDED)
            ->whereHas('biddings')
            ->whereDoesntHave('winners')
            ->each(function (Auction $auction) use (&$selected): void {
                if ($this->selectWinner($auction) !== null) {
                    $selected++;
                }
            });

        return $selected;
    }

    /**
     * Award the auction to the highest bid (earliest wins a tie), skipping bidders who forfeited it.
     */
    public function selectWinner(Auction $auction): ?AuctionWinner
    {
        if ($auction->currentStatus() !== Auction::STATUS_ENDED) {
            return null;
        }

        $forfeitedUserIds = $auction->winners()
            ->where('status', AuctionWinner::STATUS_FORFEITED)
            ->with('bidding')
            ->get()
            ->pluck('bidding.user_id')
            ->filter()
            ->all();

        $bid = Bidding::query()
            ->where('auction_id', $auction->id)
            ->whereNotIn('user_id', $forfeitedUserIds)
            ->orderByDesc('bid_amount')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($bid === null) {
            return null;
        }

        $winner = AuctionWinner::query()->create([
            'auction_id' => $auction->id,
            'bidding_id' => $bid->id,
            'status' => AuctionWinner::STATUS_AWAITING_PAYMENT,
            'payment_due_at' => now()->addDays(max(0, (int) $this->settings->get('payment_window_days'))),
        ]);

        activity('winner')
            ->performedOn($auction)
            ->withProperties(['bidding_id' => $bid->id])
            ->log('Auction :subject.auction_code was awarded to '.($bid->user?->name ?? 'a bidder'));

        if ($this->settings->get('notify_winner_automatically')) {
            $this->notifyWinner($winner);
        }

        return $winner;
    }

    public function notifyWinner(AuctionWinner $winner): bool
    {
        $user = $winner->bidding?->user;
        if ($user === null) {
            return false;
        }

        $auction = $winner->auction;
        $auction->setRelation('auction_winner', $winner);

        $sent = rescue(function () use ($user, $auction): bool {
            $user->notify(new AuctionWinnerNotification($auction));

            return true;
        }, false);

        if ($sent) {
            $winner->update(['notified_at' => now()]);
        }

        return $sent;
    }

    public function markPaid(AuctionWinner $winner, User $handler, ?string $paymentMethod = null): void
    {
        $winner->update([
            'status' => AuctionWinner::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'handled_by' => $handler->id,
        ]);

        $this->log($winner, $handler, 'recorded payment for');
    }

    public function markReleased(AuctionWinner $winner, User $handler): void
    {
        $winner->update([
            'status' => AuctionWinner::STATUS_RELEASED,
            'released_at' => now(),
            'handled_by' => $handler->id,
        ]);

        $this->log($winner, $handler, 'released the item for');
    }

    /**
     * Forfeit the current winner and offer the item to the next highest bidder.
     */
    public function forfeit(AuctionWinner $winner, ?User $handler = null): ?AuctionWinner
    {
        return DB::transaction(function () use ($winner, $handler): ?AuctionWinner {
            $winner->update([
                'status' => AuctionWinner::STATUS_FORFEITED,
                'forfeited_at' => now(),
                'handled_by' => $handler?->id,
            ]);

            $this->log($winner, $handler, 'forfeited the win on');

            return $this->selectWinner($winner->auction);
        });
    }

    public function sendEndingSoonNotices(): int
    {
        if (! $this->settings->get('notify_ending_soon')) {
            return 0;
        }

        $hours = max(1, (int) $this->settings->get('ending_soon_hours'));
        $sent = 0;

        Auction::query()
            ->where('status', Auction::STATUS_OPEN)
            ->whereNull('ending_soon_notified_at')
            ->with('item')
            ->each(function (Auction $auction) use ($hours, &$sent): void {
                if ($auction->endsAt()->gt(now()->addHours($hours))) {
                    return;
                }

                $bidders = User::query()
                    ->whereIn('id', $auction->biddings()->select('user_id'))
                    ->get();

                foreach ($bidders as $bidder) {
                    rescue(fn () => $bidder->notify(new AuctionEndingSoonNotification($auction)), report: false);
                }

                $auction->update(['ending_soon_notified_at' => now()]);
                $sent++;
            });

        return $sent;
    }

    /**
     * Remind winners whose payment is due within a day.
     */
    public function sendPaymentReminders(): int
    {
        if (! $this->settings->get('notify_payment_reminder')) {
            return 0;
        }

        $sent = 0;

        AuctionWinner::query()
            ->where('status', AuctionWinner::STATUS_AWAITING_PAYMENT)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('payment_due_at')
            ->whereBetween('payment_due_at', [now(), now()->addDay()])
            ->with(['auction.item', 'bidding.user'])
            ->each(function (AuctionWinner $winner) use (&$sent): void {
                $user = $winner->bidding?->user;
                if ($user === null) {
                    return;
                }

                rescue(fn () => $user->notify(new PaymentReminderNotification($winner)), report: false);
                $winner->update(['reminder_sent_at' => now()]);
                $sent++;
            });

        return $sent;
    }

    public function forfeitOverdueWinners(): int
    {
        if (! $this->settings->get('auto_forfeit')) {
            return 0;
        }

        $forfeited = 0;

        AuctionWinner::query()
            ->where('status', AuctionWinner::STATUS_AWAITING_PAYMENT)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->each(function (AuctionWinner $winner) use (&$forfeited): void {
                $this->forfeit($winner);
                $forfeited++;
            });

        return $forfeited;
    }

    private function log(AuctionWinner $winner, ?User $handler, string $action): void
    {
        $logger = activity('winner')->performedOn($winner->auction);

        if ($handler !== null) {
            $logger->causedBy($handler);
        }

        $logger->log(($handler?->name ?? 'The system').' '.$action.' auction '.$winner->auction->auction_code);
    }
}
