<?php

namespace App\Services;

use App\Models\Setting;

/**
 * DB-persisted site branding: site name, logo, favicon and the brand/accent
 * color scales used across the Tailwind v4 theme.
 */
class BrandingService
{
    public const DEFAULT_PRIMARY = '#2563eb';

    public const DEFAULT_ACCENT = '#f97316';

    /** @var array<int, string> */
    private const STEPS = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

    public static function siteName(): string
    {
        $name = Setting::get('site_name');

        return filled($name) ? (string) $name : config('app.name');
    }

    public static function logoUrl(?string $variant = 'icon'): ?string
    {
        $custom = Setting::get('branding.logo');
        $default = $variant === 'wide' ? 'images/logo-wide.png' : 'images/logo.png';

        if (filled($custom) && is_file(public_path((string) $custom))) {
            return (string) $custom;
        }

        return is_file(public_path($default)) ? $default : null;
    }

    public static function faviconUrl(): ?string
    {
        $favicon = Setting::get('branding.favicon');

        return filled($favicon) && is_file(public_path((string) $favicon)) ? (string) $favicon : null;
    }

    public static function brandPrimary(): string
    {
        $color = Setting::get('branding.primary');

        return self::normalizeHex((string) $color) ?? self::DEFAULT_PRIMARY;
    }

    public static function accentPrimary(): string
    {
        $color = Setting::get('branding.accent');

        return self::normalizeHex((string) $color) ?? self::DEFAULT_ACCENT;
    }

    /**
     * Inline CSS that overrides the compiled Tailwind v4 theme variables so the
     * brand colors can be changed from the admin panel at runtime.
     */
    public static function css(): string
    {
        $vars = [];

        foreach (self::STEPS as $step) {
            $vars[] = sprintf('  --color-brand-%s: %s;', $step, self::colorScale(self::brandPrimary())[$step]);
        }

        foreach (self::STEPS as $step) {
            $vars[] = sprintf('  --color-accent-%s: %s;', $step, self::colorScale(self::accentPrimary())[$step]);
        }

        return ":root {\n".implode("\n", $vars)."\n}";
    }

    /**
     * Build a Tailwind-style 50-950 palette where 600 is the base color.
     *
     * @return array<string, string>
     */
    public static function colorScale(string $hex): array
    {
        [$r, $g, $b] = self::rgb($hex);

        return [
            '950' => self::mix($r, $g, $b, 0, 0, 0, 0.45),
            '900' => self::mix($r, $g, $b, 0, 0, 0, 0.34),
            '800' => self::mix($r, $g, $b, 0, 0, 0, 0.22),
            '700' => self::mix($r, $g, $b, 0, 0, 0, 0.12),
            '600' => self::toHex($r, $g, $b),
            '500' => self::mix($r, $g, $b, 255, 255, 255, 0.15),
            '400' => self::mix($r, $g, $b, 255, 255, 255, 0.32),
            '300' => self::mix($r, $g, $b, 255, 255, 255, 0.48),
            '200' => self::mix($r, $g, $b, 255, 255, 255, 0.64),
            '100' => self::mix($r, $g, $b, 255, 255, 255, 0.79),
            '50' => self::mix($r, $g, $b, 255, 255, 255, 0.89),
        ];
    }

    private static function normalizeHex(string $color): ?string
    {
        $hex = ltrim(strtolower($color), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return strlen($hex) === 6 && ctype_xdigit($hex) ? '#'.$hex : null;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        $normalized = self::normalizeHex($hex) ?? self::DEFAULT_PRIMARY;
        $hex = ltrim($normalized, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function mix(int $r1, int $g1, int $b1, int $r2, int $g2, int $b2, float $t): string
    {
        return self::toHex(
            (int) round($r1 * (1 - $t) + $r2 * $t),
            (int) round($g1 * (1 - $t) + $g2 * $t),
            (int) round($b1 * (1 - $t) + $b2 * $t),
        );
    }

    private static function toHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
