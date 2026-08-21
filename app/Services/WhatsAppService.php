<?php

namespace App\Services;

use App\Models\Setting;

/**
 * WhatsApp support widget configuration.
 *
 * Values are resolved from the database settings first (so admins can
 * override them at runtime), falling back to values configured via the
 * WHATSAPP_* environment variables.
 */
class WhatsAppService
{
    public static function enabled(): bool
    {
        $setting = Setting::get('whatsapp.enabled');

        return $setting !== null ? (bool) $setting : (bool) config('services.whatsapp.enabled', false);
    }

    public static function isActive(): bool
    {
        return self::enabled() && filled(self::number());
    }

    public static function number(): ?string
    {
        $number = Setting::get('whatsapp.number');

        if (! filled($number)) {
            $number = config('services.whatsapp.number');
        }

        if (! filled($number)) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', (string) $number);
    }

    public static function message(): string
    {
        $message = Setting::get('whatsapp.message');

        if (! filled($message)) {
            $message = config('services.whatsapp.message');
        }

        return filled($message) ? (string) $message : 'Hello LogVerify Support, I need assistance.';
    }

    public static function label(): string
    {
        $label = Setting::get('whatsapp.label');

        if (! filled($label)) {
            $label = config('services.whatsapp.label');
        }

        return filled($label) ? (string) $label : 'Chat with us on WhatsApp';
    }

    public static function link(?string $message = null): ?string
    {
        if (! self::isActive()) {
            return null;
        }

        return 'https://wa.me/'.self::number().'?text='.rawurlencode($message ?? self::message());
    }
}
