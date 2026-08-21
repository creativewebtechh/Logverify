<?php

namespace App\Payments\Data;

class DepositIntent
{
    public function __construct(
        public string $gateway,
        public ?string $redirectUrl = null,
        public ?string $accessCode = null,
        public bool $sandbox = false,
        public array $response = [],
    ) {}

    public function isRedirectable(): bool
    {
        return filled($this->redirectUrl);
    }
}
