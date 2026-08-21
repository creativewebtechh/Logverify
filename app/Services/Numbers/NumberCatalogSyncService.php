<?php

namespace App\Services\Numbers;

use App\Models\NumberPriceHistory;
use App\Models\NumberService;
use App\Models\Provider;
use App\Services\Providers\Contracts\NumberProvider;
use App\Services\Providers\ProviderFactory;
use Illuminate\Support\Str;

class NumberCatalogSyncService
{
    public function __construct(
        protected NumberPricingService $pricing,
    ) {}

    public function syncProvider(Provider $provider): array
    {
        $driver = ProviderFactory::number($provider);

        $result = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'countries' => 0,
            'services' => 0,
            'message' => '',
        ];

        $countries = $this->countriesOf($driver);
        $prices = $this->pricesOf($driver);
        $services = $this->catalogOf($driver);

        $result['countries'] = count($countries);
        $result['services'] = count($services);

        $provider->forceFill(['total_services' => count($services), 'last_synced_at' => now()])->saveQuietly();

        if ($services === [] || $countries === [] || $prices === []) {
            $result['message'] = 'Imported '.count($services).' services and '.count($countries).' countries. Enable bulk pricing on the provider to generate the country/service matrix.';

            return $result;
        }

        $pricesByKey = collect($prices)->keyBy(fn (array $p) => $p['service_id'].':'.$p['country_id']);
        $activeKeys = [];

        foreach ($countries as $country) {
            foreach ($services as $service) {
                $priceRow = $pricesByKey->get($service['provider_service_id'].':'.$country['id']);

                if ($priceRow === null) {
                    continue;
                }

                $countryCode = strtoupper((string) ($country['code'] ?? ''));
                $catalogKey = NumberService::makeCatalogKey($provider->id, $countryCode !== '' ? $countryCode : $country['id'], $service['provider_service_id']);

                $row = NumberService::updateOrCreate(
                    ['catalog_key' => $catalogKey],
                    [
                        'provider_id' => $provider->id,
                        'provider_service_id' => $service['provider_service_id'],
                        'provider_country_id' => (string) $country['id'],
                        'country_code' => $countryCode !== '' ? $countryCode : strtoupper(substr((string) $country['id'], 0, 2)),
                        'country_name' => (string) $country['name'],
                        'name' => (string) $service['name'],
                        'slug' => Str::slug((string) $service['name'].'-'.($countryCode !== '' ? $countryCode : $country['id'])),
                        'category' => $this->normalizeCategory((string) ($service['category'] ?? 'sms')),
                        'eta' => (string) ($service['avg_time'] ?? ''),
                        'eta_seconds' => $this->etaSeconds((string) ($service['avg_time'] ?? '')),
                        'cost' => (float) $priceRow['price'],
                        'stock' => null,
                        'status' => NumberService::STATUS_ACTIVE,
                        'last_synced_at' => now(),
                    ]
                );

                $activeKeys[] = $catalogKey;

                if ($row->wasRecentlyCreated) {
                    $result['created']++;
                } else {
                    $result['updated']++;
                }

                $this->pricing->recalculate($row, null, NumberPriceHistory::CHANGED_SYNC, ['action' => 'catalog_sync']);
            }
        }

        if ($activeKeys !== []) {
            $result['deactivated'] = $provider->numberServices()
                ->where('status', NumberService::STATUS_ACTIVE)
                ->whereNotIn('catalog_key', $activeKeys)
                ->update(['status' => NumberService::STATUS_INACTIVE]);
        }

        $result['message'] = 'Imported '.$result['created'].' new services, updated '.$result['updated'].' across '.count($countries).' countries.';

        return $result;
    }

    public function syncAll(): array
    {
        $results = [];

        foreach (Provider::query()->forChannel(Provider::CHANNEL_NUMBERS)->active()->get() as $provider) {
            try {
                $results[$provider->id] = $this->syncProvider($provider);
            } catch (\Throwable $e) {
                $results[$provider->id] = [
                    'created' => 0,
                    'updated' => 0,
                    'deactivated' => 0,
                    'countries' => 0,
                    'services' => 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function countriesOf(NumberProvider $driver): array
    {
        try {
            $result = $driver->countries();

            return is_array($result['countries'] ?? null) ? $result['countries'] : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function pricesOf(NumberProvider $driver): array
    {
        try {
            $result = $driver->prices();

            return is_array($result['prices'] ?? null) ? $result['prices'] : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function catalogOf(NumberProvider $driver): array
    {
        if (! method_exists($driver, 'catalog')) {
            return [];
        }

        try {
            $catalog = $driver->catalog();

            return is_array($catalog) ? $catalog : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function normalizeCategory(string $category): string
    {
        $normalized = strtolower(trim($category));

        if (in_array($normalized, ['sms', 'whatsapp', 'telegram', 'voice', 'google', 'other', 'im', 'other_social'], true)) {
            return $normalized === 'other' || $normalized === 'other_social' ? 'sms' : $normalized;
        }

        return $normalized !== '' ? $normalized : 'sms';
    }

    private function etaSeconds(string $eta): ?int
    {
        if ($eta === '') {
            return null;
        }

        if (preg_match('/^(\d+):(\d{1,2})$/', $eta, $m)) {
            return ((int) $m[1] * 60) + (int) $m[2];
        }

        if (preg_match('/^(\d+)\s*hours?$/i', $eta, $m)) {
            return (int) $m[1] * 3600;
        }

        if (preg_match('/^(\d+)\s*minutes?$/i', $eta, $m)) {
            return (int) $m[1] * 60;
        }

        if (is_numeric($eta)) {
            $value = (int) $eta;

            return $value < 3600 ? $value * 60 : $value;
        }

        return null;
    }
}
