<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\PaymentException;

class GatewayRegistry
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function __construct()
    {
        $this->register(new PaystackGateway);
        $this->register(new MonnifyGateway);
    }

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->name()] = $gateway;
    }

    public function gateway(string $name): PaymentGateway
    {
        return $this->gateways[$name] ?? throw new PaymentException("Unsupported payment gateway: {$name}");
    }

    /** @return array<string, PaymentGateway> */
    public function all(): array
    {
        return $this->gateways;
    }
}
