<?php

namespace App\Livewire\Admin;

use App\Models\NumberPriceHistory;
use App\Models\NumberService;
use App\Models\Provider;
use App\Services\Numbers\NumberCatalogSyncService;
use App\Services\Numbers\NumberPricingService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.admin')]
class ManageNumberServices extends Component
{
    use WithPagination;

    public string $search = '';

    public string $country = '';

    public string $filterCategory = '';

    public string $filterStatus = '';

    public ?int $provider_id = null;

    public string $tab = 'catalog';

    /** @var array<int, string> */
    public array $selected = [];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $country_name = '';

    public string $category = 'sms';

    public ?string $eta = null;

    public ?int $eta_seconds = null;

    public ?string $markup_percent = null;

    public ?string $min_profit = null;

    public ?string $max_profit = null;

    public ?int $stock = null;

    public bool $featured = false;

    public bool $popular = false;

    public bool $hidden = false;

    public string $status = NumberService::STATUS_ACTIVE;

    public ?string $bulk_markup_percent = null;

    public ?string $bulk_min_profit = null;

    public ?string $bulk_max_profit = null;

    /** @var array<string, string> */
    public array $messages = [];

    protected $paginationTheme = 'tailwind';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCountry(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProviderId(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $service = NumberService::findOrFail($id);

        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->country_name = $service->country_name;
        $this->category = $service->category;
        $this->eta = $service->eta;
        $this->eta_seconds = $service->eta_seconds;
        $this->markup_percent = $service->markup_percent !== null ? (string) $service->markup_percent : null;
        $this->min_profit = $service->min_profit !== null ? (string) $service->min_profit : null;
        $this->max_profit = $service->max_profit !== null ? (string) $service->max_profit : null;
        $this->stock = $service->stock;
        $this->featured = (bool) $service->featured;
        $this->popular = (bool) $service->popular;
        $this->hidden = (bool) $service->hidden;
        $this->status = $service->status;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'country_name' => ['required', 'string', 'max:80'],
            'category' => ['required', 'string', 'max:40'],
            'eta' => ['nullable', 'string', 'max:40'],
            'eta_seconds' => ['nullable', 'integer', 'min:60', 'max:14400'],
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'min_profit' => ['nullable', 'numeric'],
            'max_profit' => ['nullable', 'numeric'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'featured' => ['boolean'],
            'popular' => ['boolean'],
            'hidden' => ['boolean'],
            'status' => ['required', Rule::in([NumberService::STATUS_ACTIVE, NumberService::STATUS_INACTIVE])],
        ]);

        $service = NumberService::findOrFail($this->editingId);

        $service->update([
            'name' => trim($this->name),
            'country_name' => trim($this->country_name),
            'category' => trim($this->category),
            'eta' => filled($this->eta) ? $this->eta : null,
            'eta_seconds' => $this->eta_seconds,
            'markup_percent' => filled($this->markup_percent) ? $this->markup_percent : null,
            'min_profit' => filled($this->min_profit) ? $this->min_profit : null,
            'max_profit' => filled($this->max_profit) ? $this->max_profit : null,
            'stock' => $this->stock,
            'featured' => $this->featured,
            'popular' => $this->popular,
            'hidden' => $this->hidden,
            'status' => $this->status,
        ]);

        app(NumberPricingService::class)->recalculate($service, auth()->user());

        $this->cancel();
        session()->flash('success', 'Number service updated.');
    }

    public function toggleFeatured(int $id): void
    {
        $service = NumberService::findOrFail($id);
        $service->update(['featured' => ! $service->featured]);
    }

    public function toggleHidden(int $id): void
    {
        $service = NumberService::findOrFail($id);
        $service->update(['hidden' => ! $service->hidden]);
    }

    public function syncProvider(int $id): void
    {
        $provider = Provider::findOrFail($id);

        try {
            $result = app(NumberCatalogSyncService::class)->syncProvider($provider);
            $this->messages[$id] = $result['message'] ?? 'Catalog sync complete';
        } catch (Throwable $e) {
            $this->messages[$id] = $e->getMessage();
        }
    }

    public function syncAll(): void
    {
        try {
            $results = app(NumberCatalogSyncService::class)->syncAll();
            $bits = collect($results)->pluck('message')->filter()->all();
            $this->messages['all'] = $bits !== [] ? implode(' — ', $bits) : 'Sync complete.';
        } catch (Throwable $e) {
            $this->messages['all'] = $e->getMessage();
        }
    }

    public function applyBulk(): void
    {
        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:number_services,id'],
            'bulk_markup_percent' => ['nullable', 'numeric', 'min:0'],
            'bulk_min_profit' => ['nullable', 'numeric'],
            'bulk_max_profit' => ['nullable', 'numeric'],
        ]);

        $params = [];

        if (filled($this->bulk_markup_percent)) {
            $params['markup_percent'] = $this->bulk_markup_percent;
        }

        if (filled($this->bulk_min_profit)) {
            $params['min_profit'] = $this->bulk_min_profit;
        }

        if (filled($this->bulk_max_profit)) {
            $params['max_profit'] = $this->bulk_max_profit;
        }

        if ($params === []) {
            $this->addError('bulk_markup_percent', 'Provide at least one value to apply.');

            return;
        }

        $ids = array_map('intval', $this->selected);
        $count = app(NumberPricingService::class)->bulkUpdate($ids, $params, auth()->user());

        session()->flash('success', "Updated pricing on {$count} number services.");

        $this->reset(['selected', 'bulk_markup_percent', 'bulk_min_profit', 'bulk_max_profit']);
    }

    public function applyDefaultMarkup(): void
    {
        $count = app(NumberPricingService::class)->applyDefaultMarkup(auth()->user());
        session()->flash('success', "Applied default markup to {$count} number services.");
    }

    public function rollback(int $id): void
    {
        $history = NumberPriceHistory::findOrFail($id);

        try {
            app(NumberPricingService::class)->rollback($history, auth()->user());
            session()->flash('success', 'Price rolled back.');
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = NumberService::query()
            ->with(['provider'])
            ->withCount('favorites')
            ->withCount('orders');

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('country_name', 'like', "%{$this->search}%")
                    ->orWhere('country_code', 'like', "%{$this->search}%");
            });
        }

        if (filled($this->country)) {
            $query->where('country_code', $this->country);
        }

        if (filled($this->filterCategory)) {
            $query->where('category', $this->filterCategory);
        }

        if (filled($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->provider_id !== null) {
            $query->where('provider_id', $this->provider_id);
        }

        return view('livewire.admin.manage-number-services', [
            'services' => $query->orderBy('sort_order')->orderBy('name')->paginate(15),
            'providers' => Provider::query()->where('channel', Provider::CHANNEL_NUMBERS)->orderBy('priority')->orderBy('id')->get(),
            'countries' => NumberService::query()->where('status', NumberService::STATUS_ACTIVE)->distinct('country_code')->orderBy('country_code')->pluck('country_code'),
            'categories' => NumberService::query()->where('status', NumberService::STATUS_ACTIVE)->distinct('category')->orderBy('category')->pluck('category'),
            'history' => NumberPriceHistory::query()->with(['numberService', 'user'])->latest()->limit(150)->get(),
            'defaultMarkup' => app(NumberPricingService::class)->defaultMarkupPercent(),
        ]);
    }
}
