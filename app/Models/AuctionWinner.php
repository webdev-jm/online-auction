<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuctionWinner extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_RELEASED = 'released';

    public const STATUS_FORFEITED = 'forfeited';

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_AWAITING_PAYMENT => 'Awaiting payment',
        self::STATUS_PAID => 'Paid',
        self::STATUS_RELEASED => 'Released',
        self::STATUS_FORFEITED => 'Forfeited',
    ];

    protected $fillable = [
        'auction_id',
        'bidding_id',
        'status',
        'payment_method',
        'payment_due_at',
        'notified_at',
        'reminder_sent_at',
        'paid_at',
        'released_at',
        'forfeited_at',
        'handled_by',
    ];

    protected $casts = [
        'payment_due_at' => 'datetime',
        'notified_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'released_at' => 'datetime',
        'forfeited_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(Bidding::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT;
    }

    public function isOverdue(): bool
    {
        return $this->isAwaitingPayment() && $this->payment_due_at !== null && $this->payment_due_at->isPast();
    }
}
