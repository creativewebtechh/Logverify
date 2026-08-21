<?php

namespace App\Services\Providers;

use App\Models\Setting;
use Throwable;

/**
 * DB-persisted configuration for the external API providers.
 *
 * Only two external channels exist: Virtual Numbers and Social Boost (SMM).
 * Digital Accounts and Tools are manual inventory and never talk to a provider.
 *
 * API keys are stored encrypted and are only ever exposed as a masked preview
 * in the admin UI.
 */
class ProviderSettings
{
    public const CHANNEL_NUMBERS = 'numbers';

    public const CHANNEL_BOOST = 'boost';

    public const KEY_DRIVER = 'driver';

    public const KEY_API_KEY = 'api_key';

    public const KEY_BASE_URL = 'base_url';

    public const KEY_ORDER_ENDPOINT = 'order_endpoint';

    public const KEY_STATUS_ENDPOINT = 'status_endpoint';

    public const KEY_BALANCE_ENDPOINT = 'balance_endpoint';

    public const KEY_SERVICES_ENDPOINT = 'services_endpoint';

    public const KEY_LAST_SYNC = 'last_sync';

    public const KEY_BALANCE = 'balance';

    public const KEY_TOTAL_SERVICES = 'total_services';

    /**
     * Sensible out-of-the-box values so the panel looks configured after
     * installation. Administrators only need to swap in real credentials.
     *
     * @var array<string, array<string, string>>
     */
    public const DEFAULTS = [
        self::CHANNEL_NUMBERS => [
            self::KEY_DRIVER => 'generic',
            self::KEY_BASE_URL => 'https://api.provider.com',
            self::KEY_ORDER_ENDPOINT => '/v1/order',
            self::KEY_STATUS_ENDPOINT => '/v1/status',
            self::KEY_BALANCE_ENDPOINT => '/v1/balance',
        ],
        self::CHANNEL_BOOST => [
            self::KEY_DRIVER => 'smmpanel',
            self::KEY_BASE_URL => 'https://panel.example.com/api/v2',
            self::KEY_ORDER_ENDPOINT => '/add',
            self::KEY_STATUS_ENDPOINT => '/status',
            self::KEY_BALANCE_ENDPOINT => '/balance',
            self::KEY_SERVICES_ENDPOINT => '/services',
        ],
    ];

    public static function get(string $channel, string $key, mixed $default = null): mixed
    {
        return Setting::get("provider.{$channel}.{$key}", $default);
    }

    public static function set(string $channel, string $key, mixed $value): void
    {
        Setting::set("provider.{$channel}.{$key}", $value);
    }

    public static function default(string $channel, string $key): string
    {
        return self::DEFAULTS[$channel][$key] ?? '';
    }

    public static function driver(string $channel): string
    {
        return (string) static::get($channel, static::KEY_DRIVER, static::default($channel, static::KEY_DRIVER));
    }

    public static function configured(string $channel): bool
    {
        return (bool) static::get($channel, static::KEY_BASE_URL)
            && static::secret($channel) !== '';
    }

    public static function endpoint(string $channel, string $key): string
    {
        return (string) static::get($channel, $key, static::default($channel, $key));
    }

    public static function apiUrl(string $channel, string $endpointKey): string
    {
        $baseUrl = rtrim((string) static::get($channel, static::KEY_BASE_URL, ''), '/');
        $endpoint = static::endpoint($channel, $endpointKey);

        return $baseUrl === '' || $endpoint === '' ? '' : $baseUrl.'/'.ltrim($endpoint, '/');
    }

    // --- Secrets --------------------------------------------------------------

    public static function secret(string $channel): string
    {
        $value = (string) static::get($channel, static::KEY_API_KEY, '');

        if ($value === '') {
            return '';
        }

        try {
            return decrypt($value);
        } catch (Throwable) {
            // Legacy plain-text value saved before encryption was introduced.
            return $value;
        }
    }

    public static function setSecret(string $channel, string $value): void
    {
        $value = trim($value);

        static::set($channel, static::KEY_API_KEY, $value === '' ? '' : encrypt($value));
    }

    public static function hasSecret(string $channel): bool
    {
        return static::secret($channel) !== '';
    }

    public static function maskedSecret(string $channel): ?string
    {
        $secret = static::secret($channel);

        if ($secret === '') {
            return null;
        }

        return '••••••••'.substr($secret, -4);
    }

    // --- Sync / balance ---------------------------------------------------------

    public static function touch(string $channel): void
    {
        static::set($channel, static::KEY_LAST_SYNC, now()->toIso8601String());
    }

    public static function lastSync(string $channel): ?string
    {
        $value = static::get($channel, static::KEY_LAST_SYNC);

        return filled($value) ? (string) $value : null;
    }

    public static function balance(string $channel): ?string
    {
        $value = static::get($channel, static::KEY_BALANCE);

        return filled($value) ? (string) $value : null;
    }

    public static function totalServices(string $channel): ?int
    {
        $value = static::get($channel, static::KEY_TOTAL_SERVICES);

        return filled($value) ? (int) $value : null;
    }
}
