<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Service;
use App\Services\Pricing\PricingEngine;
use App\Services\Providers\ProviderCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageServices extends Component
{
    use WithPagination;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $type = 'social';

    public string $platform = '';

    public ?string $price_per_unit = null;

    public ?string $cost_per_unit = null;

    public ?int $min_qty = null;

    public ?int $max_qty = null;

    public ?string $avg_time = null;

    public string $provider_service_id = '';

    public ?int $category_id = null;

    public string $image = '';

    public string $tags = '';

    public bool $featured = false;

    public bool $recommended = false;

    public bool $best_seller = false;

    public bool $popular = false;

    public bool $pinned = false;

    public bool $hidden = false;

    public bool $refill = false;

    public bool $cancel = false;

    public bool $dripfeed = false;

    public ?string $markup_percent = null;

    public ?string $min_profit = null;

    public ?string $max_profit = null;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $paginationTheme = 'tailwind';

    public function add(): void
    {
        $data = $this->normalize($this->validate($this->rules()));

        Service::create($data + ['status' => 'active']);

        $this->invalidateCatalogueCache();

        $this->cancel();

        session()->flash('success', 'Service added.');
    }

    public function update(): void
    {
        $data = $this->normalize($this->validate($this->rules()));

        $service = Service::findOrFail($this->editingId);

        $service->update($data);

        try {
            app(PricingEngine::class)->recalculateService($service, auth()->user(), 'manual');
        } catch (\Throwable $e) {
            session()->flash('error', 'Service saved but the price could not be recalculated.');
        }

        $this->invalidateCatalogueCache();

        $this->cancel();

        session()->flash('success', 'Service updated.');
    }

    public function edit(int $id): void
    {
        $service = Service::findOrFail($id);

        $this->name = $service->name;
        $this->slug = $service->slug;
        $this->description = $service->description;
        $this->type = $service->type;
        $this->platform = $service->platform ?? '';
        $this->price_per_unit = $service->price_per_unit !== null ? (string) $service->price_per_unit : null;
        $this->cost_per_unit = $service->cost_per_unit !== null ? (string) $service->cost_per_unit : null;
        $this->min_qty = $service->min_qty;
        $this->max_qty = $service->max_qty;
        $this->avg_time = $service->avg_time;
        $this->provider_service_id = (string) ($service->provider_service_id ?? '');
        $this->category_id = $service->category_id;
        $this->image = (string) ($service->image ?? '');
        $this->tags = implode(', ', $service->tags ?? []);
        $this->featured = (bool) $service->featured;
        $this->recommended = (bool) $service->recommended;
        $this->best_seller = (bool) $service->best_seller;
        $this->popular = (bool) $service->popular;
        $this->pinned = (bool) $service->pinned;
        $this->hidden = (bool) $service->hidden;
        $this->refill = (bool) $service->refill;
        $this->cancel = (bool) $service->cancel;
        $this->dripfeed = (bool) $service->dripfeed;
        $this->markup_percent = $service->markup_percent !== null ? (string) $service->markup_percent : null;
        $this->min_profit = $service->min_profit !== null ? (string) $service->min_profit : null;
        $this->max_profit = $service->max_profit !== null ? (string) $service->max_profit : null;
        $this->editingId = $service->id;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset([
            'name', 'slug', 'description', 'type', 'platform', 'price_per_unit',
            'cost_per_unit', 'min_qty', 'max_qty', 'avg_time', 'provider_service_id',
            'category_id', 'image', 'tags', 'featured', 'recommended', 'best_seller',
            'popular', 'pinned', 'hidden', 'refill', 'cancel', 'dripfeed',
            'markup_percent', 'min_profit', 'max_profit',
        ]);
        $this->editingId = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function toggleStatus(int $id): void
    {
        $service = Service::findOrFail($id);
        $service->update(['status' => $service->status === 'active' ? 'disabled' : 'active']);

        $this->invalidateCatalogueCache();
    }

    public function toggleHidden(int $id): void
    {
        $service = Service::findOrFail($id);
        $service->update(['hidden' => ! $service->hidden]);

        $this->invalidateCatalogueCache();
    }

    public function toggleFeatured(int $id): void
    {
        $service = Service::findOrFail($id);
        $service->update(['featured' => ! $service->featured]);

        $this->invalidateCatalogueCache();
    }

    public function reprice(int $id): void
    {
        $service = Service::findOrFail($id);

        try {
            $history = app(PricingEngine::class)->recalculateService($service, auth()->user(), 'manual');
            session()->flash('success', $history ? 'Service price recalculated.' : 'Service price is already up to date.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not recalculate the service price.');
        }
    }

    public function delete(int $id): void
    {
        Service::findOrFail($id)->delete();

        $this->invalidateCatalogueCache();
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash', Rule::unique('services', 'slug')->ignore($this->editingId)],
            'description' => ['required', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'platform' => ['nullable', 'string', 'max:50'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'max_qty' => ['required', 'integer', 'gte:min_qty'],
            'avg_time' => ['nullable', 'string', 'max:100'],
            'provider_service_id' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'min_profit' => ['nullable', 'numeric'],
            'max_profit' => ['nullable', 'numeric'],
            'featured' => ['boolean'],
            'recommended' => ['boolean'],
            'best_seller' => ['boolean'],
            'popular' => ['boolean'],
            'pinned' => ['boolean'],
            'hidden' => ['boolean'],
            'refill' => ['boolean'],
            'cancel' => ['boolean'],
            'dripfeed' => ['boolean'],
        ];
    }

    private function normalize(array $data): array
    {
        $data['platform'] = ($data['platform'] ?? '') !== '' ? $data['platform'] : null;
        $data['cost_per_unit'] = ($data['cost_per_unit'] ?? '') !== '' ? $data['cost_per_unit'] : null;
        $data['avg_time'] = ($data['avg_time'] ?? '') !== '' ? $data['avg_time'] : null;
        $data['provider_service_id'] = ($data['provider_service_id'] ?? '') !== '' ? $data['provider_service_id'] : null;
        $data['category_id'] = ($data['category_id'] ?? '') !== '' ? $data['category_id'] : null;
        $data['image'] = ($data['image'] ?? '') !== '' ? $data['image'] : null;
        $data['markup_percent'] = ($data['markup_percent'] ?? '') !== '' ? $data['markup_percent'] : null;
        $data['min_profit'] = ($data['min_profit'] ?? '') !== '' ? $data['min_profit'] : null;
        $data['max_profit'] = ($data['max_profit'] ?? '') !== '' ? $data['max_profit'] : null;
        $data['tags'] = ($data['tags'] ?? '') === ''
            ? null
            : array_values(array_filter(array_map('trim', explode(',', $data['tags']))));

        return $data;
    }

    private function invalidateCatalogueCache(): void
    {
        Cache::forget('dashboard.services');
        app(ProviderCatalog::class)->forget();
    }

    public function render()
    {
        return view('livewire.admin.manage-services', [
            'services' => Service::with('category')->withCount('serviceProviders')->latest()->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
