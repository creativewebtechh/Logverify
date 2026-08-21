<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Services\Providers\Contracts\BoostProvider;
use App\Services\Providers\Contracts\NumberProvider;
use App\Services\Providers\Contracts\ProviderConfig;

class ProviderFactory
{
    public static function number(?ProviderConfig $config = null): NumberProvider
    {
        $config ??= app(ProviderRouter::class)->preferred(Provider::CHANNEL_NUMBERS);

        return match ($config?->driver()) {
            'grizzly' => new GrizzlyNumberProvider($config),
            default => new GenericNumberProvider($config),
        };
    }

    public static function boost(?ProviderConfig $config = null): BoostProvider
    {
        $config ??= app(ProviderRouter::class)->preferred(Provider::CHANNEL_BOOST);

        return match ($config?->driver()) {
            'smmpanel' => new SmmPanelBoostProvider($config),
            default => new GenericBoostProvider($config),
        };
    }
}
