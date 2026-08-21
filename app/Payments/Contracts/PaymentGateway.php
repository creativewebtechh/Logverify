<?php

namespace App\Payments\Contracts;

use App\Payments\Data\DepositIntent;
use App\Payments\Data\DepositRequest;
use App\Payments\Data\PaymentNotification;
use App\Payments\PaymentMethod;

/**
 * Contract every payment gateway adapter must satisfy.
 *
 * A gateway owns how intents are created, how webhooks are authenticated,
 * and how a payment reference is verified. Normalised values flow through
 * the DTOs so the rest of the application never touches gateway quirks.
 */
interface PaymentGateway
{
    public function name(): string;

    public function label(): string;

    public function isConfigured(): bool;

    public function isTestMode(): bool;

    /** @return PaymentMethod[] */
    public function supportedMethods(): array;

    public function supportsMethod(PaymentMethod $method): bool;

    public function initialize(DepositRequest $request): DepositIntent;

    public function verify(string $reference): PaymentNotification;

    public function verifyWebhookSignature(string $payload, ?string $signature): bool;

    public function parseWebhook(array $payload): PaymentNotification;

    /**
     * Name of the event carried by a webhook payload ('' when absent).
     */
    public function eventName(array $payload): string;

    /**
     * Merchant reference carried by a webhook payload (null when absent).
     */
    public function transactionReference(array $payload): ?string;
}
