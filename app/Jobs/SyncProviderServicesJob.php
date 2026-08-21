<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Services\Providers\ServiceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProviderServicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $providerId) {}

    public function handle(ServiceSyncService $sync): void
    {
        $provider = Provider::find($this->providerId);

        if (! $provider || ! $provider->active) {
            return;
        }

        $sync->syncProvider($provider);
    }
}
