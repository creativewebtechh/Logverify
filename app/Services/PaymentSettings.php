<?php

namespace App\Services;

use App\Models\Setting;

/**
 * DB-persisted payment gateway configuration.
 *
 * Values saved here override the environment defaults so admins can manage
 * the funding gateway without touching code or .env. When a key has never
 * been saved (or is blank) the configured fallback is used.
 */
class PaymentSettings
{
    public const GATEWAY_PAYSTACK = 'paystack';

    public const GATEWAY_MONNIFY = 'monnify';

    private const PREFIX = 'payment.';

    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::get(self::PREFIX.$key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::set(self::PREFIX.$key, $value);
    }

    public static function defaultGateway(): string
    {
        $value = (string) static::get('default_gateway', '');

        return in_array($value, [self::GATEWAY_PAYSTACK, self::GATEWAY_MONNIFY], true)
            ? $value
            : self::GATEWAY_PAYSTACK;
    }

    // --- Paystack -----------------------------------------------------------

    public static function paystackPublicKey(): ?string
    {
        return static::resolved('paystack_public_key', config('paystack.public_key'));
    }

    public static function paystackSecretKey(): ?string
    {
        return static::resolved('paystack_secret_key', config('paystack.secret_key'));
    }

    public static function paystackWebhookSecret(): ?string
    {
        return static::resolved('paystack_webhook_secret', config('paystack.webhook_secret'));
    }

    public static function paystackTestMode(): bool
    {
        return (bool) static::get('paystack_test_mode', config('paystack.test_mode', true));
    }

    // --- Monnify ------------------------------------------------------------

    public static function monnifyClientKey(): ?string
    {
        return static::resolved('monnify_client_key', config('monnify.client_key'));
    }

    public static function monnifyClientSecret(): ?string
    {
        return static::resolved('monnify_client_secret', config('monnify.client_secret'));
    }

    public static function monnifyContractCode(): ?string
    {
        return static::resolved('monnify_contract_code', config('monnify.contract_code'));
    }

    public static function monnifyBaseUrl(): ?string
    {
        return static::resolved('monnify_base_url', config('monnify.base_url'));
    }

    public static function monnifyTestMode(): bool
    {
        return (bool) static::get('monnify_test_mode', config('monnify.test_mode', true));
    }

    private static function resolved(string $key, mixed $fallback): ?string
    {
        $value = static::get($key);

        if (filled($value)) {
            return (string) $value;
        }

        return $fallback ?: null;
    }
}
