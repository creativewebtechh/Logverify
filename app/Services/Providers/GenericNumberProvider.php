<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Services\Providers\Contracts\NumberProvider;
use App\Services\Providers\Contracts\ProviderConfig;
use Throwable;

class GenericNumberProvider extends GenericProvider implements NumberProvider
{
    public function __construct(?ProviderConfig $config = null)
    {
        parent::__construct($config ?? new SettingsProviderConfig(Provider::CHANNEL_NUMBERS));
    }

    public function purchase(string $orderReference, array $params): array
    {
        return $this->request('order', array_merge($params, [
            'action' => 'purchase',
            'reference' => $orderReference,
        ]));
    }

    public function countries(): array
    {
        return ['ok' => false, 'countries' => [], 'message' => 'Country listing is not supported by the generic driver.'];
    }

    public function prices(): array
    {
        return ['ok' => false, 'prices' => [], 'message' => 'Bulk pricing is not supported by the generic driver.'];
    }

    public function getStatus(string $providerReference): array
    {
        try {
            $result = $this->request('status', ['action' => 'status', 'reference' => $providerReference]);
        } catch (Throwable $e) {
            return ['success' => false, 'status' => null, 'message' => $e->getMessage(), 'raw' => []];
        }

        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
        $flag = $raw['status'] ?? $raw['sms_status'] ?? $raw['activation_status'] ?? null;

        return [
            'success' => (bool) ($result['success'] ?? false),
            'status' => $this->mapStatus($flag),
            'message' => $result['message'] ?? null,
            'raw' => $raw,
        ];
    }

    public function getSms(string $providerReference): array
    {
        try {
            $result = $this->request('status', ['action' => 'sms', 'reference' => $providerReference]);
        } catch (Throwable $e) {
            return ['success' => false, 'code' => null, 'message' => $e->getMessage()];
        }

        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
        $code = $raw['code'] ?? $raw['sms'] ?? $raw['message'] ?? null;

        if ($code === null || $code === '') {
            return ['success' => false, 'code' => null, 'message' => 'No SMS code returned yet.'];
        }

        return ['success' => true, 'code' => (string) $code, 'message' => null];
    }

    public function cancel(string $providerReference): array
    {
        try {
            $result = $this->request('status', ['action' => 'cancel', 'reference' => $providerReference]);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => (bool) ($result['success'] ?? false), 'message' => $result['message'] ?? null];
    }

    private function mapStatus(mixed $flag): ?string
    {
        if (is_bool($flag)) {
            return $flag ? 'received' : 'waiting';
        }

        if (is_numeric($flag)) {
            return match ((int) $flag) {
                8, 200, 201 => 'received',
                6 => 'cancelled',
                9, 10 => 'no_sms',
                1, 2, 3, 0 => 'waiting',
                default => 'waiting',
            };
        }

        $value = strtolower((string) $flag);

        return match (true) {
            in_array($value, ['received', 'completed', 'ok', 'success', 'sms_received'], true) => 'received',
            in_array($value, ['cancelled', 'cancel'], true) => 'cancelled',
            in_array($value, ['expired', 'no_sms', 'timeout', 'ended'], true) => 'expired',
            default => 'waiting',
        };
    }
}
