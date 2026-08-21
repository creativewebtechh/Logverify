<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * DB-persisted SMTP mail configuration.
 *
 * When enabled, these values override the environment mailer at runtime so
 * admins can point transactional email at their own SMTP server without
 * touching code or .env.
 */
class MailSettings
{
    private const PREFIX = 'smtp.';

    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::get(self::PREFIX.$key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::set(self::PREFIX.$key, $value);
    }

    public static function enabled(): bool
    {
        return (bool) self::get('enabled', false);
    }

    public static function host(): ?string
    {
        return self::nullableText('host');
    }

    public static function port(): int
    {
        $port = self::get('port', config('mail.mailers.smtp.port', 2525));

        return is_numeric($port) ? (int) $port : 2525;
    }

    public static function username(): ?string
    {
        return self::nullableText('username');
    }

    public static function password(): ?string
    {
        $stored = self::get('password');

        if (! filled($stored)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString((string) $stored);
        } catch (\Throwable) {
            return null;
        }

        return filled($decrypted) ? $decrypted : null;
    }

    public static function encryption(): ?string
    {
        $value = self::nullableText('encryption');

        return in_array($value, ['tls', 'ssl'], true) ? $value : null;
    }

    public static function fromAddress(): ?string
    {
        return self::nullableText('from_address');
    }

    public static function fromName(): ?string
    {
        return self::nullableText('from_name') ?: BrandingService::siteName();
    }

    public static function savePassword(?string $password): void
    {
        self::set('password', filled($password) ? Crypt::encryptString($password) : '');
    }

    public static function isConfigured(): bool
    {
        return self::enabled() && filled(self::host()) && filled(self::port());
    }

    /**
     * Push the DB-backed SMTP settings into the mail config for the current
     * request. No-op unless SMTP is enabled and a host is set.
     */
    public static function apply(): void
    {
        if (! self::enabled() || ! filled(self::host())) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => self::host(),
            'mail.mailers.smtp.port' => self::port(),
            'mail.mailers.smtp.username' => self::username(),
            'mail.mailers.smtp.password' => self::password(),
            'mail.mailers.smtp.encryption' => self::encryption(),
            'mail.mailers.smtp.timeout' => 30,
            'mail.from.address' => self::fromAddress() ?: config('mail.from.address'),
            'mail.from.name' => self::fromName(),
        ]);
    }

    private static function nullableText(string $key): ?string
    {
        $value = self::get($key);

        return filled($value) ? (string) $value : null;
    }
}
