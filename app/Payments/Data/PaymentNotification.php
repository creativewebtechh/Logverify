<?php

namespace App\Payments\Data;

use App\Payments\PaymentMethod;

/**
 * Normalised outcome of a gateway payment, produced by a webhook or a
 * verify call. Amounts are in the major currency unit (e.g. NGN).
 */
class PaymentNotification
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const REASON_CANCELLED = 'cancelled';

    public const REASON_FAILED = 'failed';

    public function __construct(
        public string $gateway,
        public string $reference,
        public string $status,
        public float $amountPaid = 0.0,
        public ?float $fee = null,
        public ?string $gatewayReference = null,
        public ?string $currency = null,
        public ?PaymentMethod $method = null,
        public ?string $reason = null,
        public array $raw = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function failed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function cancelled(): bool
    {
        return $this->reason === self::REASON_CANCELLED;
    }
}
