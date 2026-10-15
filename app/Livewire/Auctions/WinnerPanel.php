<?php

namespace App\Livewire\Auctions;

use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Services\AuctionLifecycle;
use App\Services\AuctionSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Winner, payment and release controls on the admin auction page.
 */
class WinnerPanel extends Component
{
    public Auction $auction;

    public string $payment_method = '';

    public bool $confirmingForfeit = false;

    public function mount(Auction $auction): void
    {
        $this->auction = $auction;
        $this->payment_method = app(AuctionSettings::class)->paymentMethods()[0] ?? '';
    }

    public function markPaid(AuctionLifecycle $lifecycle): void
    {
        $winner = $this->currentWinner(AuctionWinner::STATUS_AWAITING_PAYMENT);

        $this->validate(
            ['payment_method' => ['required', 'string', 'max:255']],
            ['payment_method.required' => 'Choose how the winner paid.']
        );

        $lifecycle->markPaid($winner, auth()->user(), $this->payment_method);
        $this->dispatch('toast', type: 'success', message: 'Payment recorded.');
    }

    public function markReleased(AuctionLifecycle $lifecycle): void
    {
        $winner = $this->currentWinner(AuctionWinner::STATUS_PAID);

        $lifecycle->markReleased($winner, auth()->user());
        $this->dispatch('toast', type: 'success', message: 'Item marked as released.');
    }

    public function forfeit(AuctionLifecycle $lifecycle): void
    {
        $winner = $this->currentWinner(AuctionWinner::STATUS_AWAITING_PAYMENT);

        $next = $lifecycle->forfeit($winner, auth()->user());
        $this->confirmingForfeit = false;

        $this->dispatch('toast', type: 'success', message: $next
            ? 'Win forfeited. The item now goes to '.$next->bidding->user->name.'.'
            : 'Win forfeited. No other bidders remain, so the item is unsold.');
    }

    public function notifyWinner(AuctionLifecycle $lifecycle): void
    {
        $this->authorizeManage();
        $winner = $this->auction->fresh()->auction_winner;
        abort_if($winner === null, 404);

        $sent = $lifecycle->notifyWinner($winner);

        $this->dispatch('toast',
            type: $sent ? 'success' : 'error',
            message: $sent ? 'Winner notification sent.' : 'The notification could not be sent. Check the mail settings.'
        );
    }

    public function render(): View
    {
        $this->auction->load(['winners.bidding.user', 'winners.handler']);

        return view('livewire.auctions.winner-panel', [
            'winner' => $this->auction->winners->sortByDesc('id')->first(),
            'history' => $this->auction->winners->sortByDesc('id')->skip(1),
            'status' => $this->auction->currentStatus(),
            'paymentMethods' => app(AuctionSettings::class)->paymentMethods(),
            'canManage' => auth()->user()->can('auction winner manage'),
        ]);
    }

    private function currentWinner(string $expectedStatus): AuctionWinner
    {
        $this->authorizeManage();

        $winner = $this->auction->fresh()->auction_winner;
        abort_if($winner === null || $winner->status !== $expectedStatus, 422, 'This action no longer applies to the current winner.');

        return $winner;
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('auction winner manage'), 403);
    }
}
