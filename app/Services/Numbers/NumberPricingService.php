<?php

namespace App\Services\Numbers;

use App\Models\NumberPriceHistory;
use App\Models\NumberService;
use App\Models\Setting;
use App\Models\User;

class NumberPricingService
{
    public function defaultMarkupPercent(): float
    {
        return (float) Setting::get('smm.pricing.numbers_markup', 30);
    }

    public function priceFor(NumberService $service): float
    {
        $cost = $service->baseCost();

        if ($cost === null) {
            return round((float) $service->price, 4);
        }

        $markup = $service->markup_percent ?? $this->defaultMarkupPercent();
        $price = $cost * (1 + (float) $markup / 100);

        if ($service->min_profit !== null) {
            $price = max($price, $cost + (float) $service->min_profit);
        }

        if ($service->max_profit !== null) {
            $price = min($price, $cost + (float) $service->max_profit);
        }

        return round(max($price, 0), 4);
    }

    public function recalculate(NumberService $service, ?User $actor = null, string $reason = 'manual', array $meta = []): ?NumberPriceHistory
    {
        $old = (float) $service->price;
        $new = $this->priceFor($service);

        if (abs($old - $new) < 0.00005) {
            return null;
        }

        $service->update([
            'price' => $new,
            'price_updated_at' => now(),
        ]);

        return $this->record($service, $old, $new, $service->baseCost(), $reason, $actor, $meta);
    }

    public function bulkUpdate(array $serviceIds, array $params, ?User $actor = null): int
    {
        $count = 0;

        foreach ($serviceIds as $id) {
            $service = NumberService::find($id);

            if ($service === null) {
                continue;
            }

            foreach (['markup_percent', 'min_profit', 'max_profit'] as $key) {
                if (array_key_exists($key, $params) && $params[$key] !== '') {
                    $service->{$key} = $params[$key];
                }
            }

            $service->save();

            if ($this->recalculate($service, $actor, NumberPriceHistory::CHANGED_BULK)) {
                $count++;
            }
        }

        return $count;
    }

    public function applyDefaultMarkup(?User $actor = null): int
    {
        $markup = $this->defaultMarkupPercent();
        $count = 0;

        NumberService::query()->where('status', NumberService::STATUS_ACTIVE)->chunkById(200, function ($services) use ($markup, $actor, &$count) {
            foreach ($services as $service) {
                if ($service->baseCost() === null) {
                    continue;
                }

                $service->markup_percent = $markup;

                if ($this->recalculate($service, $actor, NumberPriceHistory::CHANGED_BULK, ['action' => 'apply_default_markup'])) {
                    $count++;
                }
            }
        });

        return $count;
    }

    public function rollback(NumberPriceHistory $history, ?User $actor = null): void
    {
        if ($history->old_price === null) {
            throw new \InvalidArgumentException('This history entry has no previous price to restore.');
        }

        $service = $history->numberService;

        if ($service === null) {
            throw new \InvalidArgumentException('The related service no longer exists.');
        }

        $service->update([
            'price' => $history->old_price,
            'price_updated_at' => now(),
        ]);

        $this->record(
            $service,
            (float) $history->new_price,
            (float) $history->old_price,
            $service->baseCost(),
            NumberPriceHistory::CHANGED_ROLLBACK,
            $actor,
            ['rolled_back_history_id' => $history->id]
        );
    }

    public function record(NumberService $service, float $old, float $new, ?float $cost = null, string $reason = 'manual', ?User $actor = null, array $meta = []): NumberPriceHistory
    {
        return NumberPriceHistory::create([
            'number_service_id' => $service->id,
            'old_price' => $old,
            'new_price' => $new,
            'cost' => $cost,
            'reason' => $reason,
            'changed_by' => $reason,
            'user_id' => $actor?->id,
            'meta' => $meta,
        ]);
    }
}
