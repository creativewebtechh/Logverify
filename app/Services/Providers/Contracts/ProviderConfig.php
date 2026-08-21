<?php

namespace App\Services\Providers\Contracts;

/**
 * Abstraction over a single external provider connection so the drivers can be
 * pointed at either a persisted `providers` row or the legacy settings-based
 * configuration during migration.
 */
interface ProviderConfig
{
    public function channel(): string;

    public function name(): string;

    public function driver(): string;

    public function apiKey(): string;

    public function baseUrl(): string;

    public function orderEndpoint(): string;

    public function statusEndpoint(): string;

    public function balanceEndpoint(): string;

    public function servicesEndpoint(): string;

    public function providerId(): ?int;

    public function isActive(): bool;
}
