<?php

namespace App\Services\Providers;

use App\Services\Providers\Contracts\ProviderConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class GenericProvider
{
    public function __construct(protected ProviderConfig $config) {}

    public function name(): string
    {
        return $this->config->name();
    }

    /**
     * POST {base_url}/{endpoint} with a JSON body that always carries `api_key`.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, reference: string|null, number: string|null, message: string|null, status_code: int, raw: array<string, mixed>}
     */
    protected function request(string $type, array $payload): array
    {
        $endpointKey = $type === 'order' ? ProviderSettings::KEY_ORDER_ENDPOINT : ProviderSettings::KEY_STATUS_ENDPOINT;
        $url = $this->endpoint($endpointKey);
        $apiKey = $this->config->apiKey();

        if ($url === '' || $apiKey === '') {
            throw new ProviderException('Provider is not configured. Add a base URL and API key from the admin panel.');
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(30)
                ->retry(3, 100, fn (Throwable $e) => $this->shouldRetry($e), false)
                ->withHeaders(['X-API-Key' => $apiKey, 'User-Agent' => 'Logverify/1.0'])
                ->post($url, array_merge($payload, ['api_key' => $apiKey]));

            $body = $response->json();
        } catch (ConnectionException $e) {
            throw new ProviderException('Could not reach provider: '.$e->getMessage());
        }

        if (is_string($body)) {
            $body = ['message' => $body];
        }

        return $this->normalize(is_array($body) ? $body : [], $response->status());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, reference: string|null, number: string|null, message: string|null, status_code: int, raw: array<string, mixed>}
     */
    protected function normalize(array $data, int $status): array
    {
        $message = $data['message'] ?? $data['error'] ?? $data['error_message'] ?? null;
        $hasError = array_key_exists('error', $data)
            && $data['error'] !== null
            && $data['error'] !== ''
            && $data['error'] !== false;
        $flag = $data['success'] ?? $data['status'] ?? $data['code'] ?? null;

        return [
            'success' => ! $hasError && ($this->statusIsOk($flag) || ($status >= 200 && $status < 300)),
            'reference' => $data['reference'] ?? $data['order_id'] ?? $data['task_id'] ?? $data['id'] ?? $data['order'] ?? null,
            'number' => $data['number'] ?? $data['phone'] ?? $data['phone_number'] ?? $data['value'] ?? null,
            'message' => $hasError ? (string) $data['error'] : $message,
            'status_code' => $status,
            'raw' => $data,
        ];
    }

    /**
     * @return array{ok: bool, balance: string|null, message: string}
     */
    public function balance(): array
    {
        $url = $this->endpoint(ProviderSettings::KEY_BALANCE_ENDPOINT);
        $apiKey = $this->config->apiKey();

        if ($url === '' || $apiKey === '') {
            return ['ok' => false, 'balance' => null, 'message' => 'Save a base URL and API key first, then sync.'];
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(15)
                ->retry(3, 100, fn (Throwable $e) => $this->shouldRetry($e), false)
                ->withHeaders(['X-API-Key' => $apiKey, 'User-Agent' => 'Logverify/1.0'])
                ->post($url, ['api_key' => $apiKey, 'action' => 'balance']);

            $body = $response->json();

            if (is_string($body)) {
                $body = ['message' => $body];
            }

            $data = is_array($body) ? $body : [];
            $balance = $data['balance'] ?? $data['balance_usd'] ?? $data['amount'] ?? $data['credits'] ?? null;

            if ($balance === null || $balance === '') {
                return ['ok' => false, 'balance' => null, 'message' => 'Provider responded, but no balance field was returned.'];
            }

            return ['ok' => true, 'balance' => (string) $balance, 'message' => 'Balance '.$balance];
        } catch (Throwable $e) {
            return ['ok' => false, 'balance' => null, 'message' => 'Could not connect: '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, total: int|null, message: string}
     */
    public function services(): array
    {
        $url = $this->endpoint(ProviderSettings::KEY_SERVICES_ENDPOINT);
        $apiKey = $this->config->apiKey();

        if ($url === '' || $apiKey === '') {
            return ['ok' => false, 'total' => null, 'message' => 'Save a base URL and API key first, then sync services.'];
        }

        try {
            $services = $this->fetchServicesList();
            $total = count($services);

            if ($total === 0) {
                return ['ok' => true, 'total' => 0, 'message' => 'Provider responded, but no services were returned.'];
            }

            return ['ok' => true, 'total' => $total, 'message' => $total.' services available'];
        } catch (Throwable $e) {
            return ['ok' => false, 'total' => null, 'message' => 'Could not connect: '.$e->getMessage()];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        return $this->fetchServicesList();
    }

    /**
     * Fetch and normalize the provider service list.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchServicesList(): array
    {
        $url = $this->endpoint(ProviderSettings::KEY_SERVICES_ENDPOINT);
        $apiKey = $this->config->apiKey();

        if ($url === '' || $apiKey === '') {
            throw new ProviderException('Provider is not configured. Add a base URL and API key from the admin panel.');
        }

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 100, fn (Throwable $e) => $this->shouldRetry($e), false)
            ->withHeaders(['X-API-Key' => $apiKey, 'User-Agent' => 'Logverify/1.0'])
            ->post($url, ['api_key' => $apiKey, 'action' => 'services']);

        $body = $response->json();

        if (is_string($body)) {
            $body = ['message' => $body];
        }

        $data = is_array($body) ? $body : [];

        if (isset($data['error']) && $data['error'] !== null && $data['error'] !== '' && $data['error'] !== false) {
            throw new ProviderException('Provider error: '.$data['error']);
        }

        $list = $data['services'] ?? $data['data'] ?? $data;

        if (! is_array($list)) {
            return [];
        }

        $services = [];

        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['provider_service_id'] ?? $item['id'] ?? $item['service_id'] ?? null;

            if ($id === null || $id === '') {
                continue;
            }

            $services[] = [
                'provider_service_id' => (string) $id,
                'name' => (string) ($item['name'] ?? $item['service'] ?? $item['title'] ?? 'Service '.$id),
                'category' => (string) ($item['category'] ?? ''),
                'rate' => (float) ($item['rate'] ?? $item['price'] ?? $item['cost'] ?? 0),
                'min' => (int) ($item['min'] ?? $item['min_amount'] ?? $item['min_quantity'] ?? 0),
                'max' => (int) ($item['max'] ?? $item['max_amount'] ?? $item['max_quantity'] ?? 0),
                'avg_time' => (string) ($item['avg_time'] ?? $item['average_time'] ?? ''),
                'link' => (string) ($item['link'] ?? ''),
                'description' => (string) ($item['description'] ?? $item['desc'] ?? ''),
                'refill' => in_array(strtolower((string) ($item['refill'] ?? '')), ['1', 'true', 'yes'], true),
                'dripfeed' => in_array(strtolower((string) ($item['dripfeed'] ?? '')), ['1', 'true', 'yes'], true),
            ];
        }

        return $services;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function healthCheck(): array
    {
        $url = rtrim($this->config->baseUrl(), '/');
        $apiKey = $this->config->apiKey();

        if ($url === '' || $apiKey === '') {
            return ['ok' => false, 'message' => 'Save a base URL and API key first, then test again.'];
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 100, fn (Throwable $e) => $this->shouldRetry($e), false)
                ->withHeaders(['X-API-Key' => $apiKey, 'User-Agent' => 'Logverify/1.0'])
                ->get($url);

            return ['ok' => true, 'message' => 'Connection OK — provider responded with HTTP '.$response->status().'.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Could not connect: '.$e->getMessage()];
        }
    }

    protected function endpoint(string $endpointKey): string
    {
        $endpoint = match ($endpointKey) {
            ProviderSettings::KEY_ORDER_ENDPOINT => $this->config->orderEndpoint(),
            ProviderSettings::KEY_STATUS_ENDPOINT => $this->config->statusEndpoint(),
            ProviderSettings::KEY_BALANCE_ENDPOINT => $this->config->balanceEndpoint(),
            ProviderSettings::KEY_SERVICES_ENDPOINT => $this->config->servicesEndpoint(),
            default => '',
        };

        if ($endpoint === '') {
            $endpoint = ProviderSettings::default($this->config->channel(), $endpointKey);
        }

        $baseUrl = rtrim($this->config->baseUrl(), '/');

        if ($baseUrl === '' || $endpoint === '') {
            return '';
        }

        return $baseUrl.'/'.ltrim($endpoint, '/');
    }

    protected function shouldRetry(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException) {
            return in_array($e->response?->status(), [408, 425, 429, 500, 502, 503, 504], true);
        }

        return false;
    }

    protected function statusIsOk(mixed $flag): bool
    {
        if (is_bool($flag)) {
            return $flag;
        }

        if (is_numeric($flag)) {
            return in_array((int) $flag, [0, 1, 200, 201, 202], true);
        }

        return in_array(strtolower((string) $flag), ['true', 'success', 'ok', 'created'], true);
    }
}
