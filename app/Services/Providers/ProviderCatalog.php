<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

/**
 * Live catalogue of sellable provider services used by the boost order form.
 *
 * The provider service list (SMM panel "action=services") is fetched at runtime
 * and cached, then intersected with the admin-configured `services` rows (matched
 * by provider_service_id). The provider supplies live min/max, average time, link
 * format and notes; our row supplies the sell price (cost + margin) and platform.
 * When no provider is configured (or it is unreachable) the form falls back to the
 * local services table so the store keeps working.
 */
class ProviderCatalog
{
    public const CACHE_KEY = 'provider.catalog.boost';

    public const CACHE_TTL = 300;

    /**
     * Display metadata per supported platform.
     *
     * @var array<string, array{label: string, icon: string}>
     */
    public const PLATFORM_META = [
        'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'tiktok'],
        'youtube' => ['label' => 'YouTube', 'icon' => 'youtube'],
        'telegram' => ['label' => 'Telegram', 'icon' => 'telegram'],
        'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
        'spotify' => ['label' => 'Spotify', 'icon' => 'spotify'],
        'twitter' => ['label' => 'X / Twitter', 'icon' => 'twitter'],
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'whatsapp'],
        'threads' => ['label' => 'Threads', 'icon' => 'threads'],
        'discord' => ['label' => 'Discord', 'icon' => 'discord'],
        'soundcloud' => ['label' => 'SoundCloud', 'icon' => 'soundcloud'],
        'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
        'twitch' => ['label' => 'Twitch', 'icon' => 'twitch'],
        'pinterest' => ['label' => 'Pinterest', 'icon' => 'pinterest'],
        'snapchat' => ['label' => 'Snapchat', 'icon' => 'camera'],
    ];

    /**
     * Host fragments used to validate that a target link belongs to the platform.
     *
     * @var array<string, list<string>>
     */
    public const PLATFORM_HOSTS = [
        'instagram' => ['instagram.com'],
        'tiktok' => ['tiktok.com'],
        'youtube' => ['youtube.com', 'youtu.be'],
        'telegram' => ['t.me', 'telegram.me'],
        'facebook' => ['facebook.com', 'fb.com'],
        'spotify' => ['open.spotify.com', 'spotify.com'],
        'twitter' => ['x.com', 'twitter.com'],
        'whatsapp' => ['whatsapp.com', 'wa.me'],
        'threads' => ['threads.net'],
        'discord' => ['discord.com', 'discord.gg'],
        'soundcloud' => ['soundcloud.com'],
        'linkedin' => ['linkedin.com'],
        'twitch' => ['twitch.tv'],
        'pinterest' => ['pinterest.com'],
        'snapchat' => ['snapchat.com'],
    ];

    /**
     * Keywords used to guess a platform when a provider service has no local row.
     *
     * @var array<string, list<string>>
     */
    public const PLATFORM_KEYWORDS = [
        'instagram' => ['instagram'],
        'tiktok' => ['tiktok'],
        'youtube' => ['youtube', 'youtu.be'],
        'telegram' => ['telegram', 't.me'],
        'facebook' => ['facebook', 'fb.com'],
        'spotify' => ['spotify'],
        'twitter' => ['twitter', 'x.com', ' x '],
        'whatsapp' => ['whatsapp', 'wa.me'],
        'threads' => ['threads.net', ' threads'],
        'discord' => ['discord'],
        'soundcloud' => ['soundcloud'],
        'linkedin' => ['linkedin'],
        'twitch' => ['twitch'],
        'pinterest' => ['pinterest'],
        'snapchat' => ['snapchat'],
    ];

    public function __construct(private readonly ProviderRouter $router) {}

    /**
     * Merged catalogue entries for the boost channel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function entries(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->build());
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function platformLabel(string $platform): string
    {
        return self::PLATFORM_META[$platform]['label'] ?? ucfirst($platform);
    }

    public static function platformIcon(string $platform): string
    {
        return self::PLATFORM_META[$platform]['icon'] ?? 'hashtag';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function build(): array
    {
        $local = Service::query()->where('status', 'active')->get();

        $providerEntries = $this->fetchProviderCatalog();

        if ($providerEntries !== []) {
            $localByKey = $local->keyBy(fn (Service $s) => (string) $s->provider_service_id);

            return collect($providerEntries)
                ->filter(fn (array $entry) => $localByKey->has($entry['provider_service_id']))
                ->map(fn (array $entry) => $this->merge($entry, $localByKey->get($entry['provider_service_id'])))
                ->values()
                ->all();
        }

        return $local->map(fn (Service $s) => $this->localEntry($s))->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchProviderCatalog(): array
    {
        if (! $this->router->configured(Provider::CHANNEL_BOOST)) {
            return [];
        }

        try {
            $result = $this->router->call(
                Provider::CHANNEL_BOOST,
                ProviderRouter::TYPE_SERVICES,
                fn ($provider) => ['success' => true, 'services' => $provider->catalog()]
            );

            return is_array($result['services'] ?? null) ? $result['services'] : [];
        } catch (ProviderException) {
            return [];
        }
    }

    /**
     * Merge a live provider entry with our local pricing/visibility row.
     *
     * @param  array<string, mixed>  $entry
     */
    private function merge(array $entry, Service $service): array
    {
        return [
            'provider_service_id' => (string) $entry['provider_service_id'],
            'local_id' => $service->id,
            'name' => (string) ($entry['name'] ?: $service->name),
            'platform' => (string) $service->platform,
            'category' => (string) ($entry['category'] ?: $this->categoryFor($service)),
            'price_per_unit' => (float) $service->price_per_unit,
            'cost_per_unit' => $service->cost_per_unit !== null ? (float) $service->cost_per_unit : null,
            'rate' => (float) ($entry['rate'] ?? 0),
            'min' => (int) ($entry['min'] > 0 ? $entry['min'] : $service->min_qty),
            'max' => (int) ($entry['max'] > 0 ? $entry['max'] : $service->max_qty),
            'avg_time' => ($entry['avg_time'] ?? null) ?: $service->avg_time,
            'link' => ($entry['link'] ?? null) ?: null,
            'description' => ($entry['description'] ?? null) ?: $service->description,
            'refill' => (bool) ($entry['refill'] ?? false),
            'dripfeed' => (bool) ($entry['dripfeed'] ?? false),
        ];
    }

    /**
     * Fallback entry derived purely from the local services table (demo mode).
     *
     * @return array<string, mixed>
     */
    private function localEntry(Service $service): array
    {
        return [
            'provider_service_id' => (string) ($service->provider_service_id ?: $service->id),
            'local_id' => $service->id,
            'name' => (string) $service->name,
            'platform' => (string) $service->platform,
            'category' => $this->categoryFor($service),
            'price_per_unit' => (float) $service->price_per_unit,
            'cost_per_unit' => $service->cost_per_unit !== null ? (float) $service->cost_per_unit : null,
            'rate' => null,
            'min' => (int) $service->min_qty,
            'max' => (int) $service->max_qty,
            'avg_time' => $service->avg_time,
            'link' => null,
            'description' => $service->description,
            'refill' => false,
            'dripfeed' => false,
        ];
    }

    private function categoryFor(Service $service): string
    {
        return ucfirst($service->platform).' '.ucfirst($service->type);
    }

    /**
     * Guess a platform slug from provider service text (name + category).
     */
    public static function guessPlatform(string $haystack): string
    {
        $needle = strtolower($haystack);

        foreach (self::PLATFORM_KEYWORDS as $platform => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($needle, $keyword)) {
                    return $platform;
                }
            }
        }

        return 'other';
    }
}
