<?php

namespace App\Services\Providers\Contracts;

interface NumberProvider
{
    public function name(): string;

    /**
     * Purchase a virtual phone number from the provider.
     *
     * @param  array<string, mixed>  $params
     * @return array{success: bool, reference: string|null, number: string|null, message: string|null, status_code: int, raw: array<string, mixed>}
     */
    public function purchase(string $orderReference, array $params): array;

    /**
     * Fetch the provider account balance.
     *
     * @return array{ok: bool, balance: string|null, message: string}
     */
    public function balance(): array;

    /**
     * Verify credentials/reachability without placing an order.
     *
     * @return array{ok: bool, message: string}
     */
    public function healthCheck(): array;

    /**
     * List countries the provider supports.
     *
     * @return array{ok: bool, countries: array<int, array{id: string, name: string, code: string}>, message: string}
     */
    public function countries(): array;

    /**
     * List all country/service prices.
     *
     * @return array{ok: bool, prices: array<int, array{service_id: string, country_id: string, price: float|string}>, message: string}
     */
    public function prices(): array;

    /**
     * Query the activation status for a purchased number.
     *
     * @return array{success: bool, status: string|null, message: string|null, raw: array<string, mixed>}
     */
    public function getStatus(string $providerReference): array;

    /**
     * Fetch the received SMS verification code for an activation.
     *
     * @return array{success: bool, code: string|null, message: string|null}
     */
    public function getSms(string $providerReference): array;

    /**
     * Cancel an activation before it completes.
     *
     * @return array{success: bool, message: string|null}
     */
    public function cancel(string $providerReference): array;
}
