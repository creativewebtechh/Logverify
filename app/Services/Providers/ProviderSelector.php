<?php

namespace App\Services\Providers;

use App\Models\Service;
use App\Models\ServiceProvider;
use Illuminate\Support\Collection;

class ProviderSelector
{
    public const MODE_AUTO = 'auto';

    public const MODE_CHEAPEST = 'cheapest';

    public const MODE_FASTEST = 'fastest';

    public const MODE_PRIORITY = 'priority';

    public function select(Service $service, array $options = []): ?ServiceProvider
    {
        $mode = (string) ($options['mode'] ?? self::MODE_AUTO);
        $quantity = (int) ($options['quantity'] ?? 0);

        $candidates = $service->serviceProviders()
            ->active()
            ->with('provider')
            ->get()
            ->filter(fn (ServiceProvider $sp) => $sp->provider && $sp->provider->isAvailable())
            ->filter(fn (ServiceProvider $sp) => $quantity <= 0 || ($sp->min_qty <= $quantity && $sp->max_qty >= $quantity))
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return match ($mode) {
            self::MODE_FASTEST => $candidates->sortBy(fn (ServiceProvider $sp) => $this->avgTimeMinutes($sp->avg_time))->first(),
            self::MODE_CHEAPEST => $candidates->sortBy('rate')->first(),
            self::MODE_PRIORITY => $candidates->sortBy(fn (ServiceProvider $sp) => [$sp->provider->priority, $sp->rate])->first(),
            default => $this->autoPick($candidates),
        };
    }

    private function autoPick(Collection $candidates): ServiceProvider
    {
        $healthy = $candidates->filter(fn (ServiceProvider $sp) => $sp->provider->isHealthy());

        $pool = $healthy->isNotEmpty() ? $healthy : $candidates;

        return $pool->sortBy('rate')->first();
    }

    private function avgTimeMinutes(?string $avgTime): int
    {
        if (! $avgTime) {
            return PHP_INT_MAX;
        }

        $text = strtolower(trim($avgTime));
        preg_match_all('/\d+/', $text, $matches);
        $numbers = array_map('intval', $matches[0]);

        if ($numbers === []) {
            return PHP_INT_MAX;
        }

        if (str_contains($text, 'day')) {
            return 1440 * max(1, $numbers[0]);
        }

        if (str_contains($text, 'hour')) {
            $value = $numbers[0];

            if (str_contains($text, '-') && isset($numbers[1])) {
                $value = $numbers[1];
            }

            return max(1, $value) * 60;
        }

        $value = $numbers[0];

        if (str_contains($text, '-') && isset($numbers[1])) {
            $value = $numbers[1];
        }

        return max(1, $value);
    }
}
