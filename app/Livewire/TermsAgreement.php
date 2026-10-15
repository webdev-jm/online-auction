<?php

namespace App\Livewire;

use App\Models\Auction;
use App\Models\Bidding;
use App\Services\AuctionLifecycle;
use App\Services\AuctionSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The bidder's home: asks for the current terms first, then lists auctions by status.
 */
class TermsAgreement extends Component
{
    /**
     * How long ended auctions stay on the Ended tab.
     */
    private const ENDED_VISIBLE_DAYS = 14;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'open')]
    public string $tab = 'open';

    public function accept(AuctionSettings $settings): void
    {
        auth()->user()->update([
            'accepts_terms_and_conditions' => 1,
            'accepted_terms_version' => $settings->get('terms_version'),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Thanks. You can now bid on open auctions.');
    }

    public function decline(): void
    {
        auth()->user()->update([
            'accepts_terms_and_conditions' => 2,
        ]);
    }

    public function readAgain(): void
    {
        auth()->user()->update([
            'accepts_terms_and_conditions' => 0,
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['open', 'upcoming', 'ended'], true) ? $tab : 'open';
    }

    public function render(): View
    {
        $settings = app(AuctionSettings::class);
        $lifecycle = app(AuctionLifecycle::class);
        $user = auth()->user();

        if (! $user->hasAcceptedCurrentTerms()) {
            return view('livewire.terms-agreement', [
                'needsTerms' => true,
                'declined' => $user->hasDeclinedTerms(),
                'termsUpdated' => (int) $user->accepted_terms_version > 0,
                'termsHtml' => $settings->termsHtml(),
                'termsVersion' => $settings->get('terms_version'),
            ]);
        }

        $lifecycle->syncIfDue();

        $counts = [
            'open' => $this->auctionsQuery('open')->count(),
            'upcoming' => $this->auctionsQuery('upcoming')->count(),
            'ended' => $this->auctionsQuery('ended')->count(),
        ];

        $auctions = $this->auctionsQuery($this->tab)
            ->with(['item.pictures', 'auction_winner.bidding'])
            ->when($this->tab === 'ended', fn (Builder $query) => $query->orderByDesc('end')->orderByDesc('end_time'))
            ->when($this->tab !== 'ended', fn (Builder $query) => $query->orderBy($this->tab === 'open' ? 'end' : 'start')->orderBy($this->tab === 'open' ? 'end_time' : 'start_time'))
            ->get();

        $myBids = Bidding::query()
            ->where('user_id', $user->id)
            ->whereIn('auction_id', $auctions->pluck('id'))
            ->selectRaw('auction_id, MAX(bid_amount) as amount')
            ->groupBy('auction_id')
            ->pluck('amount', 'auction_id');

        $leaders = $auctions
            ->filter(fn (Auction $auction) => $auction->isOpenBidding() && $myBids->has($auction->id))
            ->mapWithKeys(fn (Auction $auction) => [$auction->id => $auction->highestBid()?->user_id]);

        return view('livewire.terms-agreement', [
            'needsTerms' => false,
            'auctions' => $auctions,
            'counts' => $counts,
            'myBids' => $myBids,
            'leaders' => $leaders,
        ]);
    }

    private function auctionsQuery(string $tab): Builder
    {
        $user = auth()->user();

        return Auction::query()
            ->whereHas('item')
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('restrict_to_company_only')
                    ->orWhere('restrict_to_company_only', false)
                    ->orWhere('company_id', $user->company_id);
            })
            ->when($tab === 'open', fn (Builder $query) => $query->where('status', Auction::STATUS_OPEN))
            ->when($tab === 'upcoming', fn (Builder $query) => $query->where('status', Auction::STATUS_UPCOMING))
            ->when($tab === 'ended', fn (Builder $query) => $query
                ->where('status', Auction::STATUS_ENDED)
                ->where('end', '>=', now()->subDays(self::ENDED_VISIBLE_DAYS)->toDateString()))
            ->when($this->search !== '', function (Builder $query) {
                $search = '%'.trim($this->search).'%';
                $query->where(function (Builder $query) use ($search) {
                    $query->where('auction_code', 'like', $search)
                        ->orWhereHas('item', function (Builder $item) use ($search) {
                            $item->where('item_number', 'like', $search)
                                ->orWhere('name', 'like', $search)
                                ->orWhere('brand', 'like', $search)
                                ->orWhereHas('specifications', function (Builder $specification) use ($search) {
                                    $specification->where('value', 'like', $search)
                                        ->orWhere('specification', 'like', $search);
                                });
                        });
                });
            });
    }
}
