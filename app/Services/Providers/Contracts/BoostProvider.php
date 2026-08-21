<?php

namespace App\Services\Providers\Contracts;

interface BoostProvider
{
    public function name(): string;

    /**
     * Place a social media growth/boost order with the provider.
     *
     * @param  array<string, mixed>  $params
     * @return array{success: bool, reference: string|null, message: string|null, status_code: int, raw: array<string, mixed>}
     */
    public function placeOrder(string $orderReference, array $params): array;

    /**
     * Fetch the provider account balance.
     *
     * @return array{ok: bool, balance: string|null, message: string}
     */
    public function balance(): array;

    /**
     * Fetch the available service list and report how many services exist.
     *
     * @return array{ok: bool, total: int|null, message: string}
     */
    public function services(): array;

    /**
     * Fetch the full, parsed service catalogue (id, name, category, rate, min,
     * max, avg_time, link format and notes) exactly as the provider returns it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array;

    /**
     * Fetch the current status of a previously placed order.
     *
     * @return array{success: bool, status: string, charge: string, starts: string, remains: string, raw: array<string, mixed>}
     */
    public function status(string $providerReference): array;

    /**
     * Verify credentials/reachability without placing an order.
     *
     * @return array{ok: bool, message: string}
     */
    public function healthCheck(): array;
}
