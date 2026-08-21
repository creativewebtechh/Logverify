<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Services\Providers\Contracts\BoostProvider;
use App\Services\Providers\Contracts\ProviderConfig;
use Illuminate\Support\Facades\Http;

/**
 * Standard SMM-panel v2 API (JAP / Perfectpanel compatible), e.g. resellersmm.com.
 *
 * POST application/x-www-form-urlencoded to {base_url}{order_endpoint} with
 * key, action=add, service, link, quantity. Success: {"order": 23501}.
 */
class SmmPanelBoostProvider implements BoostProvider
{
    public function __construct(protected ?ProviderConfig $config = null)
    {
        $this->config ??= new SettingsProviderConfig(Provider::CHANNEL_BOOST);
    }

    public function name(): string
    {
        return $this->config->name();
    }

    public function placeOrder(string $orderReference, array $params): array
    {
        $apiKey = $this->config->apiKey();

        if ($apiKey === '') {
            throw new ProviderException('Provider is not configured. Add an API key from the admin panel.');
        }

        $response = Http::asForm()
            ->timeout(30)
            ->withHeaders(['User-Agent' => 'Logverify/1.0'])
            ->post($this->endpoint(), [
                'key' => $apiKey,
                'action' => 'add',
                'service' => (string) ($params['provider_service_id'] ?? ''),
                'link' => (string) ($params['target'] ?? ''),
                'quantity' => (int) ($params['quantity'] ?? 1),
            ]);

        $body = $response->json();

        return $this->normalize(is_array($body) ? $body : ['message' => (string) $response->body()], $response->status());
    }

    public function balance(): array
    {
        $apiKey = $this->config->apiKey();

        if ($apiKey === '') {
            return ['ok' => false, 'balance' => null, 'message' => 'Save an API key first, then sync.'];
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->withHeaders(['User-Agent' => 'Logverify/1.0'])
                ->post($this->endpoint(), ['key' => $apiKey, 'action' => 'balance']);

            $data = $response->json();

            if (is_array($data) && isset($data['balance'])) {
                return ['ok' => true, 'balance' => (string) $data['balance'], 'message' => 'Balance '.$data['balance']];
            }

            if (is_array($data) && isset($data['error'])) {
                return ['ok' => false, 'balance' => null, 'message' => 'Provider error: '.$data['error']];
            }

            return ['ok' => false, 'balance' => null, 'message' => "Provider responded with HTTP {$response->status()}."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'balance' => null, 'message' => 'Could not connect: '.$e->getMessage()];
        }
    }

    public function services(): array
    {
        $apiKey = $this->config->apiKey();

        if ($apiKey === '') {
            return ['ok' => false, 'total' => null, 'message' => 'Save an API key first, then sync services.'];
        }

        try {
            $services = $this->fetchList();

            return [
                'ok' => true,
                'total' => count($services),
                'message' => count($services) === 0 ? 'Provider responded, but no services were returned.' : count($services).' services available',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'total' => null, 'message' => 'Could not connect: '.$e->getMessage()];
        }
    }

    public function catalog(): array
    {
        return $this->fetchList();
    }

    public function status(string $providerReference): array
    {
        $apiKey = $this->config->apiKey();

        if ($apiKey === '') {
            throw new ProviderException('Provider is not configured. Add an API key from the admin panel.');
        }

        $response = Http::asForm()
            ->timeout(30)
            ->withHeaders(['User-Agent' => 'Logverify/1.0'])
            ->post($this->endpoint(), [
                'key' => $apiKey,
                'action' => 'status',
                'order' => (string) $providerReference,
            ]);

        $data = $response->json();
        $data = is_array($data) ? $data : [];
        $status = $data['status'] ?? null;

        if (is_array($status)) {
            $status = $status[0] ?? null;
        }

        $hasError = array_key_exists('error', $data)
            && $data['error'] !== null
            && $data['error'] !== ''
            && $data['error'] !== false;

        return [
            'success' => ! $hasError && $response->status() >= 200 && $response->status() < 300,
            'status' => (string) ($status ?? ''),
            'charge' => (string) ($data['charge'] ?? ''),
            'starts' => (string) ($data['start_count'] ?? $data['starts'] ?? ''),
            'remains' => (string) ($data['remains'] ?? ''),
            'raw' => $data,
        ];
    }

    public function healthCheck(): array
    {
        return $this->balance();
    }

    /**
     * POST action=services and return the raw, normalized service list.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchList(): array
    {
        $apiKey = $this->config->apiKey();

        if ($apiKey === '') {
            throw new ProviderException('Provider is not configured. Add an API key from the admin panel.');
        }

        $response = Http::asForm()
            ->timeout(15)
            ->withHeaders(['User-Agent' => 'Logverify/1.0'])
            ->post($this->endpoint(), ['key' => $apiKey, 'action' => 'services']);

        $data = $response->json();

        if (! is_array($data)) {
            return [];
        }

        if (isset($data['error']) && $data['error'] !== null && $data['error'] !== '' && $data['error'] !== false) {
            throw new ProviderException('Provider error: '.$data['error']);
        }

        $list = $data['services'] ?? $data;

        if (! is_array($list)) {
            return [];
        }

        $services = [];

        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['service'] ?? $item['id'] ?? null;

            if ($id === null || $id === '') {
                continue;
            }

            $services[] = [
                'provider_service_id' => (string) $id,
                'name' => (string) ($item['name'] ?? 'Service '.$id),
                'category' => (string) ($item['category'] ?? ''),
                'rate' => (float) ($item['rate'] ?? 0),
                'min' => (int) ($item['min'] ?? 0),
                'max' => (int) ($item['max'] ?? 0),
                'avg_time' => isset($item['avg_time']) ? (string) $item['avg_time'] : null,
                'link' => isset($item['link']) ? (string) $item['link'] : null,
                'description' => isset($item['desc']) ? (string) $item['desc'] : null,
                'refill' => in_array(strtolower((string) ($item['refill'] ?? '')), ['1', 'true', 'yes'], true),
                'dripfeed' => in_array(strtolower((string) ($item['dripfeed'] ?? '')), ['1', 'true', 'yes'], true),
            ];
        }

        return $services;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, reference: string|null, message: string|null, status_code: int, raw: array<string, mixed>}
     */
    private function normalize(array $data, int $status): array
    {
        $hasError = array_key_exists('error', $data)
            && $data['error'] !== null
            && $data['error'] !== ''
            && $data['error'] !== false;

        return [
            'success' => ! $hasError && $status >= 200 && $status < 300,
            'reference' => $data['order'] ?? $data['order_id'] ?? null,
            'message' => $hasError ? (string) $data['error'] : ($data['message'] ?? null),
            'status_code' => $status,
            'raw' => $data,
        ];
    }

    private function endpoint(): string
    {
        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $endpoint = $this->config->orderEndpoint()
            ?: ProviderSettings::default(ProviderSettings::CHANNEL_BOOST, ProviderSettings::KEY_ORDER_ENDPOINT);

        if ($baseUrl === '' || $endpoint === '') {
            return '';
        }

        return $baseUrl.'/'.ltrim($endpoint, '/');
    }
}
