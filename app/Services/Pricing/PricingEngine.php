<?php

namespace App\Services\Pricing;

use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\PricingRule;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class PricingEngine
{
    public function defaultMarkupPercent(): float
    {
        return (float) Setting::get('smm.pricing.default_markup', 30);
    }

    public function resolveRule(Service $service): ?PricingRule
    {
        $providerIds = $service->serviceProviders()->pluck('provider_id')->all();

        $rules = PricingRule::query()
            ->where('enabled', true)
            ->where(function ($query) use ($service, $providerIds) {
                $query
                    ->where('scope', PricingRule::SCOPE_GLOBAL)
                    ->orWhere(fn ($q) => $q
                        ->where('scope', PricingRule::SCOPE_SERVICE)
                        ->where('service_id', $service->id))
                    ->orWhere(fn ($q) => $q
                        ->where('scope', PricingRule::SCOPE_CATEGORY)
                        ->where('category_id', $service->category_id));

                if ($providerIds !== []) {
                    $query->orWhere(fn ($q) => $q
                        ->where('scope', PricingRule::SCOPE_PROVIDER)
                        ->whereIn('provider_id', $providerIds));
                }
            })
            ->get()
            ->filter(fn (PricingRule $rule) => $rule->isApplicableNow());

        if ($rules->isEmpty()) {
            return null;
        }

        $rank = [
            PricingRule::SCOPE_SERVICE => 3,
            PricingRule::SCOPE_PROVIDER => 2,
            PricingRule::SCOPE_CATEGORY => 1,
            PricingRule::SCOPE_GLOBAL => 0,
        ];

        return $rules
            ->sortByDesc(fn (PricingRule $rule) => [$rank[$rule->scope] ?? -1, $rule->priority, $rule->id])
            ->first();
    }

    public function priceFor(Service $service, ?float $baseCost = null, ?PricingRule $rule = null): float
    {
        $cost = $baseCost ?? $service->baseCost();

        if ($cost === null) {
            $cost = (float) $service->cost_per_unit;
        }

        $rule ??= $this->resolveRule($service);
        $providerCost = null;

        if ($rule && $rule->scope === PricingRule::SCOPE_PROVIDER && $rule->provider_id) {
            $providerCost = $service->serviceProviders()
                ->active()
                ->where('provider_id', $rule->provider_id)
                ->value('rate');
        }

        $base = $providerCost ?? $cost;

        if ($rule) {
            $price = $this->applyRuleParams($base, $rule);
        } else {
            $markup = $service->markup_percent ?? $this->defaultMarkupPercent();
            $price = $base * (1 + $markup / 100);
        }

        $price = $this->applyServiceBounds($service, $price, $base);

        return round($price, 4);
    }

    public function applyRuleParams(float $cost, PricingRule $rule): float
    {
        $price = $cost;

        if ($rule->markup_percent !== null) {
            $price *= 1 + (float) $rule->markup_percent / 100;
        }

        if ($rule->fixed_profit !== null) {
            $price += (float) $rule->fixed_profit;
        }

        if ($rule->min_profit !== null) {
            $price = max($price, $cost + (float) $rule->min_profit);
        }

        if ($rule->max_profit !== null) {
            $price = min($price, $cost + (float) $rule->max_profit);
        }

        return $price;
    }

    private function applyServiceBounds(Service $service, float $price, float $cost): float
    {
        if ($service->min_profit !== null) {
            $price = max($price, $cost + (float) $service->min_profit);
        }

        if ($service->max_profit !== null) {
            $price = min($price, $cost + (float) $service->max_profit);
        }

        return max($price, 0);
    }

    public function recalculateService(Service $service, ?User $actor = null, string $reason = 'rule', array $meta = []): ?PriceHistory
    {
        $old = (float) $service->price_per_unit;
        $new = $this->priceFor($service);

        if ($this->same($old, $new)) {
            return null;
        }

        $service->update([
            'price_per_unit' => $new,
            'price_updated_at' => now(),
        ]);

        return $this->record(
            $service,
            $old,
            $new,
            $service->baseCost(),
            $reason,
            $actor,
            $meta
        );
    }

    public function applyRule(PricingRule $rule, ?User $actor = null): int
    {
        $services = Service::query()->where('status', 'active');

        match ($rule->scope) {
            PricingRule::SCOPE_SERVICE => $services->whereKey($rule->service_id),
            PricingRule::SCOPE_CATEGORY => $services->where('category_id', $rule->category_id),
            PricingRule::SCOPE_PROVIDER => $services->whereIn('id', ServiceProvider::query()
                ->where('provider_id', $rule->provider_id)
                ->pluck('service_id')),
            default => $services,
        };

        $count = 0;

        foreach ($services->get() as $service) {
            if ($this->recalculateService($service, $actor, PriceHistory::CHANGED_RULE, ['rule_id' => $rule->id, 'rule_name' => $rule->name])) {
                $count++;
            }
        }

        return $count;
    }

    public function applyToCategory(Category $category, array $params, ?User $actor = null): int
    {
        $count = 0;

        $services = Service::query()
            ->where('status', 'active')
            ->where('category_id', $category->id)
            ->get();

        foreach ($services as $service) {
            if (array_key_exists('markup_percent', $params)) {
                $service->markup_percent = $params['markup_percent'];
            }
            if (array_key_exists('min_profit', $params)) {
                $service->min_profit = $params['min_profit'];
            }
            if (array_key_exists('max_profit', $params)) {
                $service->max_profit = $params['max_profit'];
            }
            $service->save();

            if ($this->recalculateService($service, $actor, PriceHistory::CHANGED_BULK, ['category_id' => $category->id])) {
                $count++;
            }
        }

        return $count;
    }

    public function bulkUpdate(array $serviceIds, array $params, ?User $actor = null): int
    {
        $count = 0;

        foreach ($serviceIds as $id) {
            $service = Service::find($id);

            if (! $service) {
                continue;
            }

            foreach (['markup_percent', 'min_profit', 'max_profit'] as $key) {
                if (array_key_exists($key, $params) && $params[$key] !== '') {
                    $service->{$key} = $params[$key];
                }
            }

            $service->save();

            if ($this->recalculateService($service, $actor, PriceHistory::CHANGED_BULK)) {
                $count++;
            }
        }

        return $count;
    }

    public function rollback(PriceHistory $history, ?User $actor = null): void
    {
        if ($history->old_price === null) {
            throw new \InvalidArgumentException('This history entry has no previous price to restore.');
        }

        $service = $history->service;

        if (! $service) {
            throw new \InvalidArgumentException('The related service no longer exists.');
        }

        $service->update([
            'price_per_unit' => $history->old_price,
            'price_updated_at' => now(),
        ]);

        $this->record(
            $service,
            (float) $history->new_price,
            (float) $history->old_price,
            $service->baseCost(),
            PriceHistory::CHANGED_ROLLBACK,
            $actor,
            ['rolled_back_history_id' => $history->id]
        );
    }

    public function record(Service $service, float $old, float $new, ?float $cost = null, string $reason = 'manual', ?User $actor = null, array $meta = []): PriceHistory
    {
        return PriceHistory::create([
            'service_id' => $service->id,
            'provider_id' => $service->serviceProviders()->active()->min('provider_id'),
            'old_price' => $old,
            'new_price' => $new,
            'cost' => $cost,
            'reason' => $reason,
            'changed_by' => $reason,
            'user_id' => $actor?->id,
            'meta' => $meta,
        ]);
    }

    public function applyDefaultMarkup(?User $actor = null): int
    {
        $markup = $this->defaultMarkupPercent();
        $count = 0;

        Service::query()->where('status', 'active')->chunkById(200, function (Collection $services) use ($markup, $actor, &$count) {
            foreach ($services as $service) {
                $old = (float) $service->price_per_unit;
                $cost = $service->baseCost();

                if ($cost === null) {
                    continue;
                }

                $service->markup_percent = $markup;
                $new = round($cost * (1 + $markup / 100), 4);

                if (! $this->same($old, $new)) {
                    $service->price_per_unit = $new;
                    $service->price_updated_at = now();
                    $service->save();

                    $this->record($service, $old, $new, $cost, PriceHistory::CHANGED_BULK, $actor, ['action' => 'apply_default_markup']);
                    $count++;
                } else {
                    $service->saveQuietly();
                }
            }
        });

        return $count;
    }

    private function same(float $a, float $b): bool
    {
        return abs($a - $b) < 0.00005;
    }
}
