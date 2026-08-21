<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Services\Providers\Contracts\BoostProvider;
use App\Services\Providers\Contracts\ProviderConfig;

class GenericBoostProvider extends GenericProvider implements BoostProvider
{
    public function __construct(?ProviderConfig $config = null)
    {
        parent::__construct($config ?? new SettingsProviderConfig(Provider::CHANNEL_BOOST));
    }

    public function placeOrder(string $orderReference, array $params): array
    {
        return $this->request('order', array_merge($params, [
            'action' => 'order',
            'reference' => $orderReference,
        ]));
    }

    public function services(): array
    {
        return parent::services();
    }

    public function catalog(): array
    {
        return parent::catalog();
    }

    public function status(string $providerReference): array
    {
        $result = $this->request('status', ['action' => 'status', 'order' => $providerReference]);
        $raw = is_array($result['raw']) ? $result['raw'] : [];
        $status = $raw['status'] ?? $raw['status_text'] ?? null;

        if (is_array($status)) {
            $status = $status[0] ?? null;
        }

        return [
            'success' => (bool) $result['success'],
            'status' => (string) ($status ?? ''),
            'charge' => (string) ($raw['charge'] ?? $raw['price'] ?? ''),
            'starts' => (string) ($raw['start_count'] ?? $raw['starts'] ?? ''),
            'remains' => (string) ($raw['remains'] ?? ''),
            'raw' => $raw,
        ];
    }
}
