<?php

namespace App\Models;

use App\Services\AuctionSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Auction extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_UPCOMING = 'UPCOMING';

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_ENDED = 'ENDED';

    public const MODE_SEALED = 'sealed';

    public const MODE_OPEN = 'open';

    protected $fillable = [
        'item_id',
        'company_id',
        'auction_code',
        'status',
        'start',
        'start_time',
        'end',
        'end_time',
        'extension_count',
        'ending_soon_notified_at',
        'min_bid',
        'bid_limit',
        'bidding_mode',
        'min_increment',
        'max_bid',
        'show_bidders',
        'show_leading_bidder',
        'show_last_place_bidder',
        'restrict_to_company_only',
    ];

    protected $casts = [
        'ending_soon_notified_at' => 'datetime',
        'extension_count' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The current winner. Earlier winners who forfeited stay in winners().
     */
    public function auction_winner(): HasOne
    {
        return $this->hasOne(AuctionWinner::class)->latestOfMany();
    }

    public function winners(): HasMany
    {
        return $this->hasMany(AuctionWinner::class);
    }

    public function biddings(): HasMany
    {
        return $this->hasMany(Bidding::class);
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->start.' '.$this->start_time);
    }

    public function endsAt(): Carbon
    {
        return Carbon::parse($this->end.' '.$this->end_time);
    }

    /**
     * Status worked out from the clock, so it is right even before the scheduler has run.
     */
    public function currentStatus(?CarbonInterface $now = null): string
    {
        $now ??= now();

        if ($now->lt($this->startsAt())) {
            return self::STATUS_UPCOMING;
        }

        if ($now->gt($this->endsAt())) {
            return self::STATUS_ENDED;
        }

        return self::STATUS_OPEN;
    }

    public function isOpen(?CarbonInterface $now = null): bool
    {
        return $this->currentStatus($now) === self::STATUS_OPEN;
    }

    public function biddingMode(): string
    {
        if (in_array($this->bidding_mode, [self::MODE_SEALED, self::MODE_OPEN], true)) {
            return $this->bidding_mode;
        }

        return app(AuctionSettings::class)->biddingMode();
    }

    public function isOpenBidding(): bool
    {
        return $this->biddingMode() === self::MODE_OPEN;
    }

    public function minIncrement(): float
    {
        return (float) ($this->min_increment ?? app(AuctionSettings::class)->get('min_increment') ?? 0);
    }

    public function maxBid(): ?float
    {
        $maxBid = $this->max_bid ?? app(AuctionSettings::class)->get('max_bid');

        return $maxBid === null ? null : (float) $maxBid;
    }

    /**
     * Highest bid, with the earliest bid winning a tie.
     */
    public function highestBid(): ?Bidding
    {
        return $this->biddings()
            ->orderByDesc('bid_amount')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

    public function isVisibleTo(User $user): bool
    {
        return empty($this->restrict_to_company_only) || $this->company_id == $user->company_id;
    }

    public function coverPicture(): ?ItemPicture
    {
        return $this->item?->pictures->first();
    }
}
