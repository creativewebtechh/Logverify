<?php

namespace App\Livewire\Admin;

use App\Models\Provider;
use App\Services\Providers\Contracts\BoostProvider;
use App\Services\Providers\ProviderCatalog;
use App\Services\Providers\ProviderRouter;
use App\Services\Providers\ServiceSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.admin')]
class ManageIntegrations extends Component
{
    public ?int $editingId = null;

    public string $channel = Provider::CHANNEL_NUMBERS;

    public string $name = '';

    public string $driver = 'generic';

    public string $api_key = '';

    public string $base_url = '';

    public string $order_endpoint = '';

    public string $status_endpoint = '';

    public string $balance_endpoint = '';

    public string $services_endpoint = '';

    public int $priority = 0;

    public bool $active = true;

    public bool $showForm = false;

    public ?string $currency = null;

    public ?string $notes = null;

    /** @var array<int|string, string> */
    public array $messages = [];

    /** @var array<int, bool> */
    public array $revealed = [];

    public function newProvider(string $channel): void
    {
        $this->reset([
            'editingId',
            'name',
            'api_key',
            'base_url',
            'order_endpoint',
            'status_endpoint',
            'balance_endpoint',
            'services_endpoint',
            'currency',
            'notes',
        ]);

        $this->channel = $channel === Provider::CHANNEL_BOOST ? Provider::CHANNEL_BOOST : Provider::CHANNEL_NUMBERS;
        $this->driver = $this->channel === Provider::CHANNEL_BOOST ? 'smmpanel' : 'generic';
        $this->priority = $this->nextPriority();
        $this->active = true;
        $this->currency = 'NGN';
        $this->showForm = true;
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $provider = Provider::findOrFail($id);

        $this->editingId = $provider->id;
        $this->channel = $provider->channel;
        $this->name = $provider->name;
        $this->driver = $provider->driver;
        $this->api_key = '';
        $this->base_url = (string) $provider->base_url;
        $this->order_endpoint = (string) $provider->order_endpoint;
        $this->status_endpoint = (string) $provider->status_endpoint;
        $this->balance_endpoint = (string) $provider->balance_endpoint;
        $this->services_endpoint = (string) $provider->services_endpoint;
        $this->priority = (int) $provider->priority;
        $this->active = (bool) $provider->active;
        $this->currency = (string) $provider->currency;
        $this->notes = (string) ($provider->notes ?? '');
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'channel' => ['required', Rule::in([Provider::CHANNEL_NUMBERS, Provider::CHANNEL_BOOST])],
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', 'string', 'max:50'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'order_endpoint' => ['nullable', 'string', 'max:255'],
            'status_endpoint' => ['nullable', 'string', 'max:255'],
            'balance_endpoint' => ['nullable', 'string', 'max:255'],
            'services_endpoint' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
            'currency' => ['nullable', 'string', 'max:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $drivers = ProviderRouter::DRIVER_LABELS[$this->channel] ?? [];

        if (! array_key_exists($this->driver, $drivers)) {
            $this->addError('driver', 'The chosen driver is not valid for this channel.');

            return;
        }

        $data = [
            'channel' => $this->channel,
            'name' => trim($this->name),
            'driver' => $this->driver,
            'base_url' => rtrim(trim($this->base_url), '/'),
            'order_endpoint' => trim($this->order_endpoint),
            'status_endpoint' => trim($this->status_endpoint),
            'balance_endpoint' => trim($this->balance_endpoint),
            'services_endpoint' => trim($this->services_endpoint),
            'priority' => (int) $this->priority,
            'active' => $this->active,
            'currency' => trim((string) $this->currency) !== '' ? trim((string) $this->currency) : 'NGN',
            'notes' => trim((string) $this->notes) !== '' ? trim((string) $this->notes) : null,
        ];

        if ($this->editingId !== null) {
            $provider = Provider::findOrFail($this->editingId);

            if (filled(trim($this->api_key))) {
                $data['api_key'] = $this->api_key;
            }

            $provider->update($data);
            $message = 'Provider updated.';
        } else {
            $data['api_key'] = $this->api_key;
            Provider::create($data);
            $message = 'Provider added.';
        }

        $this->reset([
            'editingId',
            'name',
            'api_key',
            'base_url',
            'order_endpoint',
            'status_endpoint',
            'balance_endpoint',
            'services_endpoint',
            'currency',
            'notes',
        ]);
        $this->channel = Provider::CHANNEL_NUMBERS;
        $this->priority = 0;
        $this->active = true;
        $this->currency = 'NGN';
        $this->showForm = false;

        session()->flash('success', $message);

        $this->invalidateCatalog();
    }

    public function delete(int $id): void
    {
        Provider::findOrFail($id)->delete();
        $this->invalidateCatalog();
        session()->flash('success', 'Provider removed.');
    }

    public function toggleActive(int $id): void
    {
        $provider = Provider::findOrFail($id);
        $provider->update(['active' => ! $provider->active]);
        $this->invalidateCatalog();
    }

    public function movePriority(int $id, string $direction): void
    {
        $provider = Provider::findOrFail($id);

        $neighbor = DB::table('providers')
            ->where('channel', $provider->channel)
            ->when($direction === 'up', fn ($q) => $q->where('priority', '<', $provider->priority)->orderByDesc('priority'))
            ->when($direction === 'down', fn ($q) => $q->where('priority', '>', $provider->priority)->orderBy('priority'))
            ->value('id');

        if ($neighbor === null) {
            return;
        }

        DB::transaction(function () use ($provider, $neighbor) {
            $swap = Provider::findOrFail($neighbor);
            $tmp = $provider->priority;
            $provider->update(['priority' => $swap->priority]);
            $swap->update(['priority' => $tmp]);
        });
    }

    public function reveal(int $id): void
    {
        $this->revealed[$id] = true;
    }

    public function test(int $id): void
    {
        $provider = Provider::findOrFail($id);
        $router = app(ProviderRouter::class);

        try {
            $result = $router->call($provider->channel, ProviderRouter::TYPE_HEALTH, fn ($driver) => $driver->healthCheck());
            $this->messages[$id] = $result['message'];
        } catch (Throwable $e) {
            $this->messages[$id] = $e->getMessage();
        }

        $this->invalidateCatalog();
    }

    public function sync(int $id): void
    {
        $provider = Provider::findOrFail($id);
        $router = app(ProviderRouter::class);
        $driver = $router->driver($provider);
        $bits = [];

        try {
            $balance = $router->call($provider->channel, ProviderRouter::TYPE_BALANCE, fn () => $driver->balance());
            $provider->forceFill(['balance' => $balance['balance'] ?? null])->save();
            $bits[] = $balance['message'];
        } catch (Throwable $e) {
            $bits[] = $e->getMessage();
        }

        if ($driver instanceof BoostProvider) {
            try {
                $services = $router->call($provider->channel, ProviderRouter::TYPE_SERVICES, fn () => $driver->services());
                $provider->forceFill(['total_services' => $services['total'] ?? null])->save();
                $bits[] = $services['message'];
            } catch (Throwable $e) {
                $bits[] = $e->getMessage();
            }
        }

        $provider->forceFill(['last_synced_at' => now()])->save();
        $this->messages[$id] = implode(' — ', $bits);
        $this->invalidateCatalog();
    }

    public function checkHealth(int $id): void
    {
        $provider = Provider::findOrFail($id);

        try {
            $result = app(ServiceSyncService::class)->checkHealth($provider);
            $this->messages[$id] = $result['message'] ?? 'Health check complete';
        } catch (Throwable $e) {
            $this->messages[$id] = $e->getMessage();
        }

        $this->invalidateCatalog();
    }

    public function syncCatalog(int $id): void
    {
        $provider = Provider::findOrFail($id);

        try {
            if ($provider->channel === Provider::CHANNEL_BOOST) {
                $result = app(ServiceSyncService::class)->syncProvider($provider, true);
            } elseif ($provider->channel === Provider::CHANNEL_NUMBERS) {
                $result = app(NumberCatalogSyncService::class)->syncProvider($provider);
            } else {
                $this->messages[$id] = 'Catalog sync is not supported for this channel.';

                return;
            }

            $this->messages[$id] = $result['message'] ?? 'Catalog sync complete';
        } catch (Throwable $e) {
            $this->messages[$id] = $e->getMessage();
        }

        $this->invalidateCatalog();
    }

    public function syncAll(): void
    {
        try {
            $results = app(ServiceSyncService::class)->syncAll();
            $bits = collect($results)->pluck('message')->filter()->all();
            $this->messages['all'] = $bits !== [] ? implode(' — ', $bits) : 'All providers synced.';
        } catch (Throwable $e) {
            $this->messages['all'] = $e->getMessage();
        }

        $this->invalidateCatalog();
    }

    private function invalidateCatalog(): void
    {
        app(ProviderCatalog::class)->forget();
    }

    private function nextPriority(): int
    {
        return (int) Provider::query()->where('channel', $this->channel)->max('priority') + 1;
    }

    public function render()
    {
        return view('livewire.admin.manage-integrations', [
            'providers' => Provider::query()->orderBy('channel')->orderBy('priority')->orderBy('id')->get(),
            'driverLabels' => ProviderRouter::DRIVER_LABELS,
            'logStats' => DB::table('provider_logs')
                ->select('provider_id', DB::raw('COUNT(*) as total'), DB::raw("SUM(status = 'failed') as failures"))
                ->groupBy('provider_id')
                ->get()
                ->keyBy('provider_id'),
        ]);
    }
}
