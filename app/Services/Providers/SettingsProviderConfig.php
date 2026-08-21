<?php

namespace App\Services\Providers;

use App\Services\Providers\Contracts\ProviderConfig;

/**
 * Bridges the legacy settings-table configuration into the ProviderConfig
 * contract so drivers, the router and the admin panel all share one interface.
 * Used only while no `providers` rows exist for a channel (pre-upgrade installs).
 */
class SettingsProviderConfig implements ProviderConfig
{
    public function __construct(protected string $channel) {}

    public function channel(): string
    {
        return $this->channel;
    }

    public function name(): string
    {
        return ucfirst($this->channel).' provider';
    }

    public function driver(): string
    {
        return ProviderSettings::driver($this->channel);
    }

    public function apiKey(): string
    {
        return ProviderSettings::secret($this->channel);
    }

    public function baseUrl(): string
    {
        return (string) ProviderSettings::get($this->channel, ProviderSettings::KEY_BASE_URL, '');
    }

    public function orderEndpoint(): string
    {
        return ProviderSettings::endpoint($this->channel, ProviderSettings::KEY_ORDER_ENDPOINT);
    }

    public function statusEndpoint(): string
    {
        return ProviderSettings::endpoint($this->channel, ProviderSettings::KEY_STATUS_ENDPOINT);
    }

    public function balanceEndpoint(): string
    {
        return ProviderSettings::endpoint($this->channel, ProviderSettings::KEY_BALANCE_ENDPOINT);
    }

    public function servicesEndpoint(): string
    {
        return ProviderSettings::endpoint($this->channel, ProviderSettings::KEY_SERVICES_ENDPOINT);
    }

    public function providerId(): ?int
    {
        return null;
    }

    public function isActive(): bool
    {
        return true;
    }
}
