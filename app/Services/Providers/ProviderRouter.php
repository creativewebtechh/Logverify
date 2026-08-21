<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Models\ProviderLog;
use App\Services\Providers\Contracts\BoostProvider;
use App\Services\Providers\Contracts\NumberProvider;
use App\Services\Providers\Contracts\ProviderConfig;
use Throwable;

/**
 * Resolves which provider driver to use for a channel and transparently fails
 * over to the next configured provider when the preferred one errors. Every
 * attempt is recorded in the provider_logs table for the admin usage view.
 */
class ProviderRouter
{
    public const TYPE_ORDER = 'order';

    public const TYPE_BALANCE = 'balance';

    public const TYPE_SERVICES = 'services';

    public const TYPE_HEALTH = 'health';

    public const TYPE_STATUS = 'status';

    public const TYPE_SMS = 'sms';

    public const TYPE_CANCEL = 'cancel';

    public const TYPE_COUNTRIES = 'countries';

    public const TYPE_PRICES = 'prices';

    public const DRIVER_LABELS = [
        Provider::CHANNEL_NUMBERS => [
            'generic' => 'Generic JSON API',
            'grizzly' => 'Grizzly SMS (sms-activate)',
        ],
        Provider::CHANNEL_BOOST => [
            'generic' => 'Generic JSON API',
            'smmpanel' => 'SMM panel v2',
        ],
    ];

    /**
     * Active providers for a channel ordered by priority. Falls back to the
     * legacy settings-based configuration when the table has no rows yet.
     *
     * @return array<int, ProviderConfig>
     */
    public function configs(string $channel, ?int $preferredId = null): array
    {
        $providers = Provider::query()
            ->forChannel($channel)
            ->active()
            ->orderByRaw('(id = ?) DESC', [$preferredId ?? -1])
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($providers->isNotEmpty()) {
            return $providers->all();
        }

        if (ProviderSettings::configured($channel)) {
            return [new SettingsProviderConfig($channel)];
        }

        return [];
    }

    public function preferred(string $channel): ?ProviderConfig
    {
        $configs = $this->configs($channel);

        return $configs[0] ?? null;
    }

    /**
     * True when a usable provider (base URL + API key) is available for the
     * channel. Orders against an unconfigured channel are simulated locally.
     */
    public function configured(string $channel): bool
    {
        $config = $this->preferred($channel);

        return $config !== null && $config->apiKey() !== '' && $config->baseUrl() !== '';
    }

    public function driver(ProviderConfig $config): NumberProvider|BoostProvider
    {
        return $config->channel() === Provider::CHANNEL_NUMBERS
            ? ProviderFactory::number($config)
            : ProviderFactory::boost($config);
    }

    public function number(?ProviderConfig $config = null): NumberProvider
    {
        return ProviderFactory::number($config ?? $this->preferred(Provider::CHANNEL_NUMBERS));
    }

    public function boost(?ProviderConfig $config = null): BoostProvider
    {
        return ProviderFactory::boost($config ?? $this->preferred(Provider::CHANNEL_BOOST));
    }

    /**
     * Run $fn against each configured provider (priority order) until one
     * succeeds. Records every attempt in provider_logs.
     *
     * When $deferredLogs is passed, attempt records are appended to it instead of
     * being written immediately. This lets callers run the provider call inside a
     * DB transaction and flush the records only after the transaction resolves, so
     * a rolled-back order still leaves a useful audit trail.
     *
     * @param  callable(NumberProvider|BoostProvider): array  $fn
     * @param  ProviderConfig|null  $used  populated with the provider that won
     * @param  int|null  $preferredId  a specific provider to try first
     * @param  array<int, array<string, mixed>>|null  $deferredLogs  sink for pending log records
     * @param  int|null  $orderId  order id to stamp onto audit log rows
     * @return array the successful provider response
     *
     * @throws ProviderException when every provider fails
     */
    public function call(string $channel, string $type, callable $fn, ?ProviderConfig &$used = null, ?int $preferredId = null, ?array &$deferredLogs = null, ?int $orderId = null): array
    {
        $configs = $this->configs($channel, $preferredId);
        $lastError = null;

        if ($configs === []) {
            throw new ProviderException('No provider is configured for '.ucfirst($channel).' orders. Add credentials from the admin panel.');
        }

        foreach ($configs as $config) {
            $driver = $this->driver($config);
            $startedAt = hrtime(true);

            try {
                $result = $fn($driver);

                $ok = is_array($result)
                    && ($result['success'] ?? $result['ok'] ?? false) === true;
                $error = is_array($result) ? (string) ($result['message'] ?? '') : '';

                $this->record($config, $channel, $type, $ok, $startedAt, $ok ? null : ($error ?: 'Provider returned an unsuccessful response.'), $deferredLogs, $orderId);

                if ($ok) {
                    $this->touch($config);
                    $used = $config;

                    return $result;
                }

                $lastError = $error ?: 'Provider returned an unsuccessful response.';
            } catch (Throwable $e) {
                $this->record($config, $channel, $type, false, $startedAt, $e->getMessage(), $deferredLogs, $orderId);
                $lastError = $e->getMessage();
            }
        }

        throw new ProviderException((string) $lastError);
    }

    private function record(ProviderConfig $config, string $channel, string $type, bool $ok, int $startedAt, ?string $error, ?array &$deferredLogs = null, ?int $orderId = null): void
    {
        $payload = [
            'provider_id' => $config->providerId(),
            'order_id' => $orderId,
            'channel' => $channel,
            'type' => $type,
            'status' => $ok ? 'success' : 'failed',
            'response_time_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
            'error' => $error,
        ];

        if ($deferredLogs !== null) {
            $deferredLogs[] = $payload;
        } else {
            ProviderLog::create($payload);
        }
    }

    private function touch(ProviderConfig $config): void
    {
        if ($config instanceof Provider) {
            $config->update(['last_used_at' => now()]);
        }
    }
}
