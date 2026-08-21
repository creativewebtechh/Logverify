<?php

namespace App\Payments\Data;

use App\Models\User;
use App\Payments\PaymentMethod;

class DepositRequest
{
    public function __construct(
        public User $user,
        public float $amount,
        public string $reference,
        public string $callbackUrl,
        public PaymentMethod $method,
    ) {}
}
