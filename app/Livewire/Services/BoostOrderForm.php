<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\ConfirmsTransactionPin;
use App\Models\Service;
use App\Services\OrderService;
use App\Services\Providers\ProviderCatalog;
use App\Services\WalletService;
use Illuminate\Support\Collection;
use Livewire\Component;

class BoostOrderForm extends Component
{
    use ConfirmsTransactionPin;

    public ?string $platform = null;

    public string $search = '';

    public ?string $category = null;

    public ?string $provider_service_id = null;

    public string $target = '';

    public $quantity = 100;

    public bool $submitted = false;

    public bool $insufficientBalance = false;

    public function mount(?int $service = null): void
    {
        if ($service === null) {
            return;
        }

        $model = Service::where('id', $service)->where('status', 'active')->first();

        if (! $model) {
            return;
        }

        $entry = collect($this->catalogue())->firstWhere('local_id', $model->id);

        if (! $entry) {
            return;
        }

        $this->provider_service_id = (string) $entry['provider_service_id'];
        $this->platform = (string) $entry['platform'];
        $this->category = (string) $entry['category'];
        $this->quantity = (int) $entry['min'];
    }

    // --- Selection ------------------------------------------------------------

    public function selectPlatform(?string $platform): void
    {
        $this->platform = $platform ?: null;
        $this->category = null;
        $this->provider_service_id = null;
        $this->submitted = false;
    }

    public function updatedPlatform(): void
    {
        $this->category = null;
        $this->provider_service_id = null;
        $this->submitted = false;
    }

    public function updatedCategory(): void
    {
        $this->provider_service_id = null;
        $this->submitted = false;
    }

    public function updatedProviderServiceId(): void
    {
        $entry = $this->selectedEntry();

        if (! $entry) {
            return;
        }

        $this->platform = (string) $entry['platform'];
        $this->category = (string) $entry['category'];
        $this->quantity = max((int) $this->quantity, (int) $entry['min']);
        $this->insufficientBalance = false;
        $this->submitted = false;
    }

    public function refreshCatalog(): void
    {
        app(ProviderCatalog::class)->forget();

        if ($this->provider_service_id !== null && $this->selectedEntry() === null) {
            $this->provider_service_id = null;
        }

        session()->flash('success', 'Service list refreshed from the provider.');
    }

    // --- Derived pricing / limits --------------------------------------------

    public function unitPrice(): float
    {
        $entry = $this->selectedEntry();

        return $entry ? (float) $entry['price_per_unit'] : 0.0;
    }

    public function charge(): float
    {
        return round($this->unitPrice() * (int) $this->quantity, 2);
    }

    public function minQty(): int
    {
        return (int) ($this->selectedEntry()['min'] ?? 0);
    }

    public function maxQty(): int
    {
        return (int) ($this->selectedEntry()['max'] ?? 0);
    }

    // --- Live validation -------------------------------------------------------

    public function linkError(): ?string
    {
        $entry = $this->selectedEntry();

        if (! $entry) {
            return null;
        }

        if (trim($this->target) === '') {
            return 'Enter the link to your profile or post.';
        }

        $parts = parse_url(trim($this->target));

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return 'Enter a full link starting with https:// — e.g. https://www.tiktok.com/@user/video/123';
        }

        $host = strtolower((string) $parts['host']);
        $expected = ProviderCatalog::PLATFORM_HOSTS[$entry['platform']] ?? null;

        if ($expected) {
            $matches = collect($expected)->contains(fn (string $domain) => $host === $domain || str_ends_with($host, '.'.$domain));

            if (! $matches) {
                $label = ProviderCatalog::platformLabel((string) $entry['platform']);

                return "That link doesn't look like a {$label} link. Paste the {$label} profile or post URL.";
            }
        }

        return null;
    }

    public function quantityError(): ?string
    {
        $entry = $this->selectedEntry();

        if (! $entry) {
            return null;
        }

        $quantity = (int) $this->quantity;
        $min = (int) $entry['min'];
        $max = (int) $entry['max'];

        if ($quantity < $min || $quantity > $max) {
            return 'Quantity must be between '.number_format($min).' and '.number_format($max).'.';
        }

        return null;
    }

    // --- Order ----------------------------------------------------------------

    public function placeOrder(OrderService $orders)
    {
        $this->submitted = true;
        $this->insufficientBalance = false;

        $prepared = $this->validatedPurchase();

        if ($prepared === null) {
            return;
        }

        [$service, $entry, $charge] = $prepared;

        $this->openPinModal($charge);
    }

    public function confirmPurchase(OrderService $orders)
    {
        if (! $this->verifyPin()) {
            return;
        }

        $this->submitted = true;
        $this->insufficientBalance = false;

        $prepared = $this->validatedPurchase();

        if ($prepared === null) {
            return;
        }

        [$service, $entry, $charge] = $prepared;

        try {
            $order = $orders->buyBoost(
                auth()->user(),
                $service,
                (int) $this->quantity,
                ['target' => trim($this->target)],
                (int) $entry['min'],
                (int) $entry['max']
            );
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', 'Order placed successfully. It\'s now being processed.');

        $this->reset(['provider_service_id', 'category', 'target']);
        $this->quantity = 100;
    }

    /**
     * Re-run every pre-flight check for a boost order. Returns
     * [Service, entry, charge] when the order can proceed, otherwise null.
     *
     * @return array{0: Service, 1: array<string, mixed>, 2: float}|null
     */
    private function validatedPurchase(): ?array
    {
        $entry = $this->selectedEntry();

        if (! $entry) {
            session()->flash('error', 'Choose a service to continue.');

            return null;
        }

        $service = Service::find($entry['local_id']);

        if (! $service || $service->status !== 'active') {
            session()->flash('error', 'This service is no longer available.');

            return null;
        }

        $linkError = $this->linkError();
        $qtyError = $this->quantityError();

        if ($linkError) {
            $this->addError('target', $linkError);
        }

        if ($qtyError) {
            $this->addError('quantity', $qtyError);
        }

        if ($linkError || $qtyError) {
            return null;
        }

        $charge = $service->priceFor((int) $this->quantity);

        if (! app(WalletService::class)->hasBalance(auth()->user(), $charge)) {
            $this->insufficientBalance = true;

            return null;
        }

        return [$service, $entry, $charge];
    }

    // --- Render ---------------------------------------------------------------

    public function render()
    {
        $entries = $this->catalogue();
        $selected = $this->selectedEntry();

        return view('livewire.services.boost-order-form', [
            'entries' => $entries,
            'platforms' => $this->platforms($entries),
            'categories' => $this->categoryNames($entries),
            'services' => $this->serviceOptions($entries),
            'selected' => $selected,
            'balance' => app(WalletService::class)->balance(auth()->user()),
            'unitPrice' => $this->unitPrice(),
            'charge' => $this->charge(),
            'min' => $this->minQty(),
            'max' => $this->maxQty(),
            'avgTime' => $selected ? ($selected['avg_time'] ?? null) : null,
            'linkError' => $this->linkError(),
            'qtyError' => $this->quantityError(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(): array
    {
        return app(ProviderCatalog::class)->entries();
    }

    private function selectedEntry(): ?array
    {
        if ($this->provider_service_id === null || $this->provider_service_id === '') {
            return null;
        }

        return collect($this->catalogue())->firstWhere('provider_service_id', $this->provider_service_id);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{key: string, label: string, icon: string, count: int}>
     */
    private function platforms(array $entries): array
    {
        $present = collect($entries)->pluck('platform')->unique()->values()->all();
        $ordered = array_values(array_intersect(array_keys(ProviderCatalog::PLATFORM_META), $present));

        foreach ($present as $platform) {
            if (! in_array($platform, $ordered, true)) {
                $ordered[] = $platform;
            }
        }

        return array_map(fn (string $platform) => [
            'key' => $platform,
            'label' => ProviderCatalog::platformLabel($platform),
            'icon' => ProviderCatalog::platformIcon($platform),
            'count' => collect($entries)->where('platform', $platform)->count(),
        ], $ordered);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, string>
     */
    private function categoryNames(array $entries): array
    {
        return $this->filtered($entries)
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function serviceOptions(array $entries): array
    {
        return $this->filtered($entries)
            ->sortBy(fn (array $entry) => [$entry['platform'], $entry['category'], $entry['name']])
            ->values()
            ->all();
    }

    /**
     * Apply the platform / category / search filters to the catalogue.
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    private function filtered(array $entries): Collection
    {
        return collect($entries)
            ->when($this->platform, fn ($c) => $c->where('platform', $this->platform))
            ->when($this->category, fn ($c) => $c->where('category', $this->category))
            ->when($this->search !== '', fn ($c) => $c->filter(
                fn (array $entry) => str_contains(strtolower((string) $entry['name']), strtolower($this->search))
            ));
    }
}
