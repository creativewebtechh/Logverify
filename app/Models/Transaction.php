<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_REFERRAL_COMMISSION = 'referral_commission';

    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    // Payment status (gateway settlement outcome for funding transactions)
    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_SUCCESS = 'success';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_AMOUNT_MISMATCH = 'amount_mismatch';

    public const PAYMENT_CURRENCY_MISMATCH = 'currency_mismatch';

    // Payment methods
    public const METHOD_CARD = 'card';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_USSD = 'ussd';

    public const METHOD_QR = 'qr';

    public const METHOD_MOBILE_MONEY = 'mobile_money';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'fee',
        'balance_after',
        'status',
        'payment_status',
        'reference',
        'gateway_reference',
        'gateway',
        'payment_method',
        'description',
        'meta',
        'gateway_response',
        'webhook_payload',
        'ip_address',
        'device',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'meta' => 'array',
            'gateway_response' => 'array',
            'webhook_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
