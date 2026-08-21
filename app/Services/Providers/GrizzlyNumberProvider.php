<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Services\Providers\Contracts\NumberProvider;
use App\Services\Providers\Contracts\ProviderConfig;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * sms-activate / Grizzly SMS-compatible activation API.
 *
 * GET {base_url}/stubs/handler_api.php?api_key=...&action=...
 * Responses are plain text, e.g. ACCESS_NUMBER:12345678:79991112233.
 */
class GrizzlyNumberProvider implements NumberProvider
{
    public function __construct(protected ?ProviderConfig $config = null)
    {
        $this->config ??= new SettingsProviderConfig(Provider::CHANNEL_NUMBERS);
    }

    public function name(): string
    {
        return $this->config->name();
    }

    public function purchase(string $orderReference, array $params): array
    {
        try {
            $text = $this->requestAction('getNumber', [
                'service' => (string) ($params['provider_service_id'] ?? ''),
                'country' => (string) ($params['country'] ?? ''),
            ]);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'reference' => null,
                'number' => null,
                'message' => $e->getMessage(),
                'status_code' => 0,
                'raw' => [],
            ];
        }

        if (preg_match('/^ACCESS_NUMBER:(\d+):(.+)$/', $text, $matches)) {
            return [
                'success' => true,
                'reference' => $matches[1],
                'number' => $matches[2],
                'message' => null,
                'status_code' => 200,
                'raw' => ['raw' => $text],
            ];
        }

        return [
            'success' => false,
            'reference' => null,
            'number' => null,
            'message' => $text === '' ? 'Provider returned an empty response.' : $text,
            'status_code' => 200,
            'raw' => ['raw' => $text],
        ];
    }

    public function balance(): array
    {
        try {
            $text = $this->requestAction('getBalance');
        } catch (Throwable $e) {
            return ['ok' => false, 'balance' => null, 'message' => 'Could not connect: '.$e->getMessage()];
        }

        if (preg_match('/^ACCESS_BALANCE:(.+)$/', $text, $matches)) {
            return ['ok' => true, 'balance' => trim($matches[1]), 'message' => 'Balance '.trim($matches[1])];
        }

        return ['ok' => false, 'balance' => null, 'message' => $text === '' ? 'Provider returned an empty response.' : $text];
    }

    public function healthCheck(): array
    {
        return $this->balance();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        try {
            $text = $this->requestAction('getServices');
        } catch (Throwable) {
            return [];
        }

        $body = str_contains($text, 'ACCESS_SERVICES:') ? substr($text, strpos($text, 'ACCESS_SERVICES:') + 16) : $text;

        $services = [];

        foreach (preg_split('/\R/', trim($body)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            $parts = explode(':', $line);
            $id = (string) array_shift($parts);
            $name = trim((string) array_shift($parts));
            $category = count($parts) > 0 ? trim((string) $parts[0]) : 'sms';

            if ($id === '' || $name === '') {
                continue;
            }

            $services[] = [
                'provider_service_id' => $id,
                'name' => $name,
                'category' => $category,
                'rate' => 0,
                'min' => 0,
                'max' => 0,
                'avg_time' => '',
                'link' => '',
                'description' => '',
                'refill' => false,
                'dripfeed' => false,
            ];
        }

        return $services;
    }

    public function countries(): array
    {
        try {
            $text = $this->requestAction('getCountries');
        } catch (Throwable $e) {
            return ['ok' => false, 'countries' => [], 'message' => $e->getMessage()];
        }

        $body = str_contains($text, 'ACCESS_COUNTRIES:') ? substr($text, strpos($text, 'ACCESS_COUNTRIES:') + 16) : $text;

        $countries = [];

        foreach (preg_split('/\R/', trim($body)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            $parts = explode(':', $line);
            $id = (string) array_shift($parts);
            $name = trim((string) array_shift($parts));
            $code = count($parts) > 0 ? strtoupper(trim((string) $parts[0])) : '';

            if ($id === '' || $name === '') {
                continue;
            }

            $countries[] = ['id' => $id, 'name' => $name, 'code' => $code];
        }

        return ['ok' => true, 'countries' => $countries, 'message' => count($countries).' countries available'];
    }

    public function prices(): array
    {
        try {
            $text = $this->requestAction('getPrices');
        } catch (Throwable $e) {
            return ['ok' => false, 'prices' => [], 'message' => $e->getMessage()];
        }

        $body = str_contains($text, 'ACCESS_PRICES:') ? substr($text, strpos($text, 'ACCESS_PRICES:') + 14) : $text;

        $prices = [];

        foreach (preg_split('/\R/', trim($body)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            $parts = explode(':', $line);

            if (count($parts) < 3) {
                continue;
            }

            $serviceId = (string) array_shift($parts);
            $countryId = (string) array_shift($parts);
            $price = (string) array_shift($parts);

            if ($serviceId === '' || $countryId === '' || $price === '') {
                continue;
            }

            $prices[] = ['service_id' => $serviceId, 'country_id' => $countryId, 'price' => $price];
        }

        return ['ok' => true, 'prices' => $prices, 'message' => count($prices).' price rows available'];
    }

    public function getStatus(string $providerReference): array
    {
        try {
            $text = $this->requestAction('getStatus', ['id' => $providerReference]);
        } catch (Throwable $e) {
            return ['success' => false, 'status' => null, 'message' => $e->getMessage(), 'raw' => []];
        }

        $status = match (true) {
            str_starts_with($text, 'STATUS_OK') => 'received',
            str_starts_with($text, 'STATUS_CANCEL') => 'cancelled',
            str_starts_with($text, 'STATUS_NO_ACTIVATION') => 'no_sms',
            in_array($text, ['6'], true) => 'received',
            in_array($text, ['8'], true) => 'cancelled',
            in_array($text, ['9'], true) => 'no_sms',
            str_starts_with($text, 'STATUS_') => 'waiting',
            default => null,
        };

        if ($status === null) {
            return ['success' => false, 'status' => null, 'message' => $text === '' ? 'Empty provider response.' : $text, 'raw' => ['raw' => $text]];
        }

        return ['success' => true, 'status' => $status, 'message' => $text, 'raw' => ['raw' => $text]];
    }

    public function getSms(string $providerReference): array
    {
        try {
            $text = $this->requestAction('getSms', ['id' => $providerReference]);
        } catch (Throwable $e) {
            return ['success' => false, 'code' => null, 'message' => $e->getMessage()];
        }

        if (preg_match('/^CODE:(.+)$/', $text, $matches)) {
            return ['success' => true, 'code' => trim($matches[1]), 'message' => null];
        }

        return ['success' => false, 'code' => null, 'message' => $text === '' ? 'Empty provider response.' : $text];
    }

    public function cancel(string $providerReference): array
    {
        try {
            $text = $this->requestAction('setStatus', ['id' => $providerReference, 'status' => '8']);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => $text];
    }

    private function requestAction(string $action, array $params = []): string
    {
        $apiKey = $this->config->apiKey();
        $endpoint = $this->endpoint();

        if ($apiKey === '' || $endpoint === '') {
            throw new ProviderException('Provider is not configured. Add an API key from the admin panel.');
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'Logverify/1.0'])
            ->get($endpoint, array_merge(['api_key' => $apiKey, 'action' => $action], $params));

        return trim((string) $response->body());
    }

    private function endpoint(): string
    {
        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $configured = $this->config->orderEndpoint();
        $genericDefault = ProviderSettings::default(ProviderSettings::CHANNEL_NUMBERS, ProviderSettings::KEY_ORDER_ENDPOINT);

        $endpoint = $configured !== '' && $configured !== $genericDefault
            ? $configured
            : '/stubs/handler_api.php';

        return $baseUrl === '' ? '' : $baseUrl.'/'.ltrim($endpoint, '/');
    }
}
