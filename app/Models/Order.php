<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_EXPIRED = 'expired';

    public const CHANNEL_NUMBERS = 'numbers';

    public const CHANNEL_BOOST = 'boost';

    public const CHANNEL_ACCOUNTS = 'accounts';

    public const CHANNEL_TOOLS = 'tools';

    public const SMS_WAITING = 'waiting';

    public const SMS_RECEIVED = 'received';

    public const SMS_CANCELLED = 'cancelled';

    public const SMS_EXPIRED = 'expired';

    public const SMS_NO_SMS = 'no_sms';

    protected $fillable = [
        'user_id',
        'orderable_type',
        'orderable_id',
        'title',
        'quantity',
        'unit_price',
        'total',
        'status',
        'channel',
        'provider_reference',
        'provider_id',
        'phone_number',
        'sms_status',
        'sms_code',
        'sms_code_at',
        'expires_at',
        'refunded_at',
        'reference',
        'payment_method',
        'gateway',
        'meta',
        'paid_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'sms_code_at' => 'datetime',
            'expires_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function scopeNumbers($query)
    {
        return $query->where('channel', self::CHANNEL_NUMBERS)
            ->orWhere('orderable_type', NumberService::class)
            ->orWhere('orderable_type', Number::class);
    }

    public function scopeBoost($query)
    {
        return $query->where('channel', self::CHANNEL_BOOST)
            ->orWhere('orderable_type', Service::class);
    }

    public function scopeWaitingSms($query)
    {
        return $query->where('sms_status', self::SMS_WAITING);
    }

    public function scopeExpiring($query)
    {
        return $query
            ->whereIn('status', [self::STATUS_PAID, self::STATUS_PROCESSING])
            ->where(function ($query) {
                $query
                    ->whereNotNull('expires_at')->where('expires_at', '<', now())
                    ->orWhere(function ($query) {
                        $query->whereNull('expires_at')->where('created_at', '<', now()->subMinutes(10));
                    });
            });
    }

    public function timeline(): array
    {
        return $this->statusHistory->map(fn (OrderStatusHistory $h) => [
            'status' => $h->status,
            'note' => $h->note,
            'meta' => $h->meta,
            'created_at' => $h->created_at,
        ])->all();
    }

    public function isNumber(): bool
    {
        return $this->channel === self::CHANNEL_NUMBERS
            || $this->orderable_type === NumberService::class
            || $this->orderable_type === Number::class;
    }

    public function isBoost(): bool
    {
        return $this->channel === self::CHANNEL_BOOST
            || $this->orderable_type === Service::class;
    }

    public function isWaitingSms(): bool
    {
        return $this->isNumber()
            && in_array($this->status, [self::STATUS_PAID, self::STATUS_PROCESSING], true)
            && $this->sms_status === self::SMS_WAITING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRefundable(): bool
    {
        if ($this->refunded_at !== null) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_PROCESSING,
            self::STATUS_FAILED,
        ], true);
    }

    public function timeRemainingSeconds(): int
    {
        if ($this->expires_at === null) {
            return 0;
        }

        return max(0, $this->expires_at->diffInSeconds(now()));
    }
}
