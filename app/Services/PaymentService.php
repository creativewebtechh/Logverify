<?php

namespace App\Services;

use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\DepositIntent;
use App\Payments\Data\DepositRequest;
use App\Payments\Data\PaymentNotification;
use App\Payments\GatewayRegistry;
use App\Payments\PaymentMethod;

/**
 * App-facing payment facade.
 *
 * Resolves gateway adapters through the registry and exposes normalised
 * intent creation, verification and webhook parsing. Money always flows in
 * the major currency unit (e.g. NGN); each gateway converts to its own
 * minor-unit representation internally.
 */
class PaymentService
{
    public function __construct(private GatewayRegistry $registry) {}

    public function gateway(?string $name = null): PaymentGateway
    {
        return $this->registry->gateway($name ?: PaymentSettings::defaultGateway());
    }

    /** @return array<string, PaymentGateway> */
    public function availableGateways(): array
    {
        return $this->registry->all();
    }

    public function isConfigured(): bool
    {
        return $this->registry->gateway('paystack')->isConfigured();
    }

    public function monnifyIsConfigured(): bool
    {
        return $this->registry->gateway('monnify')->isConfigured();
    }

    public function publicKey(): ?string
    {
        return PaymentSettings::paystackPublicKey();
    }

    public function initializeDeposit(
        User $user,
        float $amount,
        string $reference,
        string $callbackUrl,
        ?string $gateway = null,
        ?PaymentMethod $method = null,
    ): DepositIntent {
        $gatewayName = $gateway ?: PaymentSettings::defaultGateway();

        return $this->gateway($gatewayName)->initialize(
            new DepositRequest($user, $amount, $reference, $callbackUrl, $method ?: PaymentMethod::Card)
        );
    }

    public function verify(string $reference, ?string $gateway = null): PaymentNotification
    {
        return $this->gateway($gateway)->verify($reference);
    }

    public function verifyWebhookSignature(string $gateway, string $payload, ?string $signature): bool
    {
        return $this->registry->gateway($gateway)->verifyWebhookSignature($payload, $signature);
    }

    public function parseWebhook(string $gateway, array $payload): PaymentNotification
    {
        return $this->registry->gateway($gateway)->parseWebhook($payload);
    }
}
