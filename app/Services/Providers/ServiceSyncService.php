<?php

namespace App\Services\Providers;

use App\Events\OrderStatusChanged;
use App\Models\Category;
use App\Models\Order;
use App\Models\PriceHistory;
use App\Models\Provider;
use App\Models\ProviderLog;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Str;
use Throwable;

class ServiceSyncService
{
    public const TYPE_KEYWORDS = [
        'followers' => ['followers', 'follower'],
        'likes' => ['likes', 'like'],
        'views' => ['views', 'view'],
        'comments' => ['comments', 'comment', 'reviews', 'commentaires'],
        'subscribers' => ['subscribers', 'subscriber', 'abonnes'],
        'reactions' => ['reactions', 'reaction'],
        'shares' => ['shares', 'share'],
        'saves' => ['saves', 'save'],
        'members' => ['members', 'group'],
        'joins' => ['joins', 'join'],
        'invites' => ['invites', 'invite'],
        'boost' => ['boost', 'cheap', 'fast', 'premium', 'quality', 'high quality'],
    ];

    public function __construct(
        private readonly ProviderRouter $router,
        private readonly PricingEngine $pricingEngine,
    ) {}

    public function syncAll(bool $applyPricing = true): array
    {
        $providers = Provider::query()->forChannel(Provider::CHANNEL_BOOST)->active()->get();
        $results = [];

        foreach ($providers as $provider) {
            $results[$provider->id] = $this->syncProvider($provider, $applyPricing);
        }

        return $results;
    }

    public function syncProvider(Provider $provider, bool $applyPricing = true): array
    {
        if (! $provider->isConfigured()) {
            return ['ok' => false, 'message' => 'Provider is not configured. Add a base URL and API key.'];
        }

        $startedAt = hrtime(true);

        try {
            $catalog = $this->fetchCatalog($provider);

            if ($catalog === []) {
                return ['ok' => false, 'message' => 'Provider responded, but the service catalogue was empty.'];
            }

            $affected = [];
            $created = 0;

            foreach ($catalog as $entry) {
                $service = $this->upsertEntry($provider, $entry);

                if ($service) {
                    $affected[$service->id] = $service;
                }

                if ($service && ! empty($entry['created'])) {
                    $created++;
                }
            }

            $this->pruneStale($provider, collect($catalog)->pluck('provider_service_id')->all());
            $this->finishProviderSync($provider, $applyPricing, $affected, $startedAt);

            return [
                'ok' => true,
                'created' => $created,
                'updated' => count($affected),
                'message' => count($affected).' services linked, '.$created.' created.',
            ];
        } catch (Throwable $e) {
            $this->recordLog($provider, 'services', 'failed', $startedAt, $e->getMessage());
            $provider->recordCall(false);
            $provider->update(['last_error' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkHealth(Provider $provider): array
    {
        if (! $provider->isConfigured()) {
            $this->setHealth($provider, 'unknown', null, 'Provider is not configured.');

            return ['ok' => false, 'message' => 'Provider is not configured.'];
        }

        $startedAt = hrtime(true);

        try {
            $driver = $this->router->boost($provider);
            $result = $driver->healthCheck();

            $ms = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $ok = (bool) ($result['ok'] ?? false);
            $message = (string) ($result['message'] ?? '');

            $this->setHealth($provider, $ok ? 'healthy' : 'degraded', $ms, $ok ? null : ($message ?: 'Health check failed.'));

            return ['ok' => $ok, 'message' => $message ?: 'Provider is reachable.', 'response_time_ms' => $ms];
        } catch (Throwable $e) {
            $ms = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $this->setHealth($provider, 'unhealthy', $ms, $e->getMessage());

            return ['ok' => false, 'message' => $e->getMessage(), 'response_time_ms' => $ms];
        }
    }

    public function checkAllHealth(): array
    {
        $providers = Provider::query()->forChannel(Provider::CHANNEL_BOOST)->active()->get();
        $results = [];

        foreach ($providers as $provider) {
            $results[$provider->id] = $this->checkHealth($provider);
        }

        return $results;
    }

    public function syncProviderBalance(Provider $provider): array
    {
        if (! $provider->isConfigured()) {
            return ['ok' => false, 'message' => 'Provider is not configured.'];
        }

        $startedAt = hrtime(true);

        try {
            $driver = $this->router->boost($provider);
            $result = $driver->balance();
            $ms = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $ok = (bool) ($result['ok'] ?? false);

            if ($ok) {
                $provider->update(['balance' => (string) ($result['balance'] ?? ''), 'last_synced_at' => now()]);
            }

            $this->recordLog($provider, 'balance', $ok ? 'success' : 'failed', $startedAt, $ok ? null : ($result['message'] ?? 'Balance sync failed.'));
            $provider->recordCall($ok);

            return ['ok' => $ok, 'message' => (string) ($result['message'] ?? ''), 'balance' => $result['balance'] ?? null, 'response_time_ms' => $ms];
        } catch (Throwable $e) {
            $ms = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $this->recordLog($provider, 'balance', 'failed', $startedAt, $e->getMessage());
            $provider->recordCall(false);

            return ['ok' => false, 'message' => $e->getMessage(), 'response_time_ms' => $ms];
        }
    }

    public function syncOrderStatuses(?int $limit = null): array
    {
        $query = Order::query()
            ->where('orderable_type', Service::class)
            ->whereIn('status', [Order::STATUS_PROCESSING, Order::STATUS_PAID])
            ->whereNotNull('meta->provider_reference')
            ->orderBy('id');

        if ($limit) {
            $query->limit($limit);
        }

        $checked = 0;
        $updated = 0;
        $failed = 0;

        foreach ($query->get() as $order) {
            $checked++;
            $providerId = $order->meta['provider_id'] ?? null;
            $providerReference = (string) ($order->meta['provider_reference'] ?? '');

            if (! $providerId || $providerReference === '') {
                continue;
            }

            $provider = Provider::find($providerId);

            if (! $provider || ! $provider->isConfigured()) {
                continue;
            }

            try {
                $driver = $this->router->boost($provider);
                $result = $driver->status($providerReference);

                if (! $result['success']) {
                    $failed++;

                    continue;
                }

                $canonical = $this->mapProviderStatus((string) ($result['status'] ?? ''));

                if ($canonical && $canonical !== $order->status) {
                    $oldStatus = $order->status;
                    $order->status = $canonical;

                    if ($canonical === Order::STATUS_COMPLETED) {
                        $order->completed_at = now();
                    }

                    $order->save();

                    $this->pushTimeline($order, $canonical, 'Auto-synced from provider ('.$result['status'].').', [
                        'provider_status' => (string) ($result['status'] ?? ''),
                        'remains' => (string) ($result['remains'] ?? ''),
                        'starts' => (string) ($result['starts'] ?? ''),
                        'source' => 'sync',
                    ]);

                    OrderStatusChanged::dispatch($order, $oldStatus, $canonical, 'Auto-synced from provider.');
                    $updated++;
                }
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['checked' => $checked, 'updated' => $updated, 'failed' => $failed];
    }

    public function mapProviderStatus(string $status): ?string
    {
        $needle = strtolower(trim($status));

        if ($needle === '') {
            return null;
        }

        return match (true) {
            str_contains($needle, 'complete'), str_contains($needle, 'fulfil'), str_contains($needle, 'success'),
            str_contains($needle, 'processed'), str_contains($needle, 'done'), str_contains($needle, '100%'),
            str_contains($needle, 'delivered'), $needle === 'finished' => Order::STATUS_COMPLETED,
            str_contains($needle, 'cancel'), str_contains($needle, 'reject'), str_contains($needle, 'decline'),
            str_contains($needle, 'refund') => Order::STATUS_FAILED,
            str_contains($needle, 'error'), $needle === 'fail', str_contains($needle, 'failed') => Order::STATUS_FAILED,
            str_contains($needle, 'partial'), str_contains($needle, 'in progress'), str_contains($needle, 'inprocess'),
            str_contains($needle, 'pending'), str_contains($needle, 'processing'), str_contains($needle, 'started'),
            str_contains($needle, 'await') => Order::STATUS_PROCESSING,
            default => null,
        };
    }

    public function setHealth(Provider $provider, string $status, ?int $ms, ?string $error = null): void
    {
        $previous = $provider->health_status;

        $provider->update([
            'health_status' => $status,
            'response_time_ms' => $ms,
            'last_health_check_at' => now(),
            'last_error' => $error,
        ]);

        $provider->recordCall($status !== 'unhealthy');
        $this->recordLog($provider, 'health', $status === 'unhealthy' ? 'failed' : 'success', null, $error);
    }

    private function fetchCatalog(Provider $provider): array
    {
        $result = $this->router->call(
            Provider::CHANNEL_BOOST,
            ProviderRouter::TYPE_SERVICES,
            fn ($driver) => ['success' => true, 'services' => $driver->catalog()],
            $used,
            $provider->id,
        );

        return is_array($result['services'] ?? null) ? $result['services'] : [];
    }

    private function upsertEntry(Provider $provider, array &$entry): ?Service
    {
        $providerServiceId = (string) ($entry['provider_service_id'] ?? '');
        $rate = (float) ($entry['rate'] ?? 0);

        if ($providerServiceId === '') {
            return null;
        }

        $pivot = ServiceProvider::query()
            ->where('provider_id', $provider->id)
            ->where('provider_service_id', $providerServiceId)
            ->first();

        if ($pivot) {
            $service = $pivot->service;
        } else {
            $service = Service::query()
                ->where('provider_service_id', $providerServiceId)
                ->whereDoesntHave('serviceProviders', fn ($q) => $q->where('provider_id', $provider->id))
                ->first();
        }

        if (! $service) {
            $service = $this->createService($provider, $entry, $providerServiceId);
            $entry['created'] = true;
        }

        if (! $service) {
            return null;
        }

        $this->updateService($service, $entry, $providerServiceId, $rate);

        ServiceProvider::updateOrCreate(
            ['provider_id' => $provider->id, 'service_id' => $service->id],
            [
                'provider_service_id' => $providerServiceId,
                'rate' => $rate,
                'min_qty' => max(1, (int) ($entry['min'] ?? 1)),
                'max_qty' => max(1, (int) ($entry['max'] ?? 1)),
                'avg_time' => ($entry['avg_time'] ?? null) ?: null,
                'refill' => (bool) ($entry['refill'] ?? false),
                'cancel' => (bool) ($entry['cancel'] ?? false),
                'dripfeed' => (bool) ($entry['dripfeed'] ?? false),
                'status' => ServiceProvider::STATUS_ACTIVE,
                'last_synced_at' => now(),
            ]
        );

        return $service;
    }

    private function createService(Provider $provider, array $entry, string $providerServiceId): ?Service
    {
        $name = (string) ($entry['name'] ?? '');
        $haystack = $name.' '.($entry['category'] ?? '');
        $platform = ProviderCatalog::guessPlatform($haystack);
        $type = $this->guessType($haystack);

        if ($name === '' || $platform === 'other') {
            return null;
        }

        $category = $this->categoryFor($platform, $type);
        $slug = $this->uniqueSlug($name, $provider->id, $providerServiceId);

        return Service::create([
            'name' => $name,
            'slug' => $slug,
            'description' => ($entry['description'] ?? null) ?: null,
            'type' => $type,
            'platform' => $platform,
            'price_per_unit' => 0,
            'cost_per_unit' => (float) ($entry['rate'] ?? 0),
            'min_qty' => max(1, (int) ($entry['min'] ?? 1)),
            'max_qty' => max(1, (int) ($entry['max'] ?? 1)),
            'avg_time' => ($entry['avg_time'] ?? null) ?: null,
            'category_id' => $category?->id,
            'provider_service_id' => $providerServiceId,
            'refill' => (bool) ($entry['refill'] ?? false),
            'dripfeed' => (bool) ($entry['dripfeed'] ?? false),
            'status' => Service::STATUS_ACTIVE,
        ]);
    }

    private function updateService(Service $service, array $entry, string $providerServiceId, float $rate): void
    {
        $updates = [];

        if (! $service->description && ($entry['description'] ?? null)) {
            $updates['description'] = (string) $entry['description'];
        }

        if (! $service->avg_time && ($entry['avg_time'] ?? null)) {
            $updates['avg_time'] = (string) $entry['avg_time'];
        }

        if ($service->provider_service_id !== $providerServiceId) {
            $updates['provider_service_id'] = $providerServiceId;
        }

        if ($updates !== []) {
            $service->update($updates);
        }
    }

    private function pruneStale(Provider $provider, array $liveProviderServiceIds): void
    {
        ServiceProvider::query()
            ->where('provider_id', $provider->id)
            ->where('status', ServiceProvider::STATUS_ACTIVE)
            ->whereNotIn('provider_service_id', $liveProviderServiceIds)
            ->update(['status' => ServiceProvider::STATUS_INACTIVE]);
    }

    private function finishProviderSync(Provider $provider, bool $applyPricing, array $affected, int $startedAt): void
    {
        if ($applyPricing) {
            foreach ($affected as $service) {
                $this->refreshServicePricing($service);
            }
        }

        $provider->update([
            'total_services' => ServiceProvider::query()->where('provider_id', $provider->id)->where('status', ServiceProvider::STATUS_ACTIVE)->count(),
            'last_synced_at' => now(),
        ]);

        $provider->recordCall(true);
        $this->recordLog($provider, 'services', 'success', $startedAt, null);
    }

    private function refreshServicePricing(Service $service): void
    {
        $cheapest = $service->serviceProviders()->active()->orderBy('rate')->first();
        $minQty = $service->serviceProviders()->active()->min('min_qty');
        $maxQty = $service->serviceProviders()->active()->max('max_qty');

        $updates = [];

        if ($cheapest && ((float) $service->cost_per_unit !== (float) $cheapest->rate)) {
            $updates['cost_per_unit'] = (float) $cheapest->rate;
        }

        if ($minQty !== null && (int) $service->min_qty !== (int) $minQty) {
            $updates['min_qty'] = (int) $minQty;
        }

        if ($maxQty !== null && (int) $service->max_qty !== (int) $maxQty) {
            $updates['max_qty'] = (int) $maxQty;
        }

        if ($updates !== []) {
            $service->update($updates);
        }

        $this->pricingEngine->recalculateService($service, null, PriceHistory::CHANGED_SYNC, ['source' => 'provider_sync']);
    }

    private function categoryFor(string $platform, string $type): ?Category
    {
        $name = trim(ucfirst($platform).' '.ucfirst($type));

        return Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'platform' => $platform === 'other' ? null : $platform,
                'status' => Category::STATUS_ACTIVE,
            ]
        );
    }

    private function uniqueSlug(string $name, int $providerId, string $providerServiceId): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'service' : Str::limit($base, 60, '');
        $suffix = substr(md5($providerId.'-'.$providerServiceId), 0, 6);
        $slug = $base.'-'.$suffix;
        $n = 2;

        while (Service::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix.'-'.$n;
            $n++;
        }

        return $slug;
    }

    private function guessType(string $haystack): string
    {
        $needle = strtolower($haystack);

        foreach (self::TYPE_KEYWORDS as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($needle, $keyword)) {
                    return $type;
                }
            }
        }

        return 'boost';
    }

    private function recordLog(Provider $provider, string $type, string $status, ?int $startedAt, ?string $error): void
    {
        ProviderLog::create([
            'provider_id' => $provider->id,
            'channel' => $provider->channel,
            'type' => $type,
            'status' => $status,
            'response_time_ms' => $startedAt !== null ? max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)) : null,
            'error' => $error,
        ]);
    }

    private function pushTimeline(Order $order, string $status, string $note, array $meta): void
    {
        $order->statusHistory()->create([
            'status' => $status,
            'note' => $note,
            'meta' => $meta,
        ]);
    }
}
