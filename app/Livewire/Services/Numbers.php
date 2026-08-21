<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\ConfirmsTransactionPin;
use App\Models\Favorite;
use App\Models\NumberService;
use App\Models\Order;
use App\Services\Numbers\NumberPurchaseService;
use Livewire\Component;
use Livewire\WithPagination;

class Numbers extends Component
{
    use ConfirmsTransactionPin;
    use WithPagination;

    public string $tab = 'browse';

    public string $country = '';

    public string $category = '';

    public string $search = '';

    public string $sort = 'popular';

    public ?int $confirmingId = null;

    public ?int $pendingServiceId = null;

    protected $paginationTheme = 'tailwind';

    public function updatedCountry(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function showTab(string $tab): void
    {
        $this->tab = $tab === 'mine' ? 'mine' : 'browse';
        $this->resetPage();
    }

    public function confirm(int $serviceId): void
    {
        $this->confirmingId = $serviceId;
    }

    public function dismiss(): void
    {
        $this->confirmingId = null;
    }

    public function openPin(int $serviceId): void
    {
        $service = NumberService::query()->findOrFail($serviceId);

        $this->pendingServiceId = $serviceId;
        $this->confirmingId = null;

        $this->openPinModal((float) $service->price);
    }

    public function confirmPurchase(NumberPurchaseService $purchase): void
    {
        if (! $this->verifyPin()) {
            return;
        }

        $service = NumberService::query()->find($this->pendingServiceId);
        $this->pendingServiceId = null;

        if ($service === null || $service->status !== NumberService::STATUS_ACTIVE || $service->hidden) {
            session()->flash('error', 'This service is currently unavailable.');

            return;
        }

        try {
            $order = $purchase->purchase(auth()->user(), $service);
            $this->tab = 'mine';
            session()->flash('success', 'Number reserved. Waiting for the SMS code — Order #'.$order->reference.'.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleFavorite(int $serviceId): void
    {
        $existing = Favorite::query()
            ->where('user_id', auth()->id())
            ->where('number_service_id', $serviceId)
            ->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            Favorite::create(['user_id' => auth()->id(), 'number_service_id' => $serviceId]);
        }
    }

    public function refresh(int $orderId, NumberPurchaseService $purchase): void
    {
        $order = $this->ownedOrder($orderId);
        $purchase->poll($order);

        session()->flash('success', 'SMS status refreshed.');
    }

    public function refreshDue(NumberPurchaseService $purchase): void
    {
        $orders = Order::numbers()
            ->where('user_id', auth()->id())
            ->where('status', Order::STATUS_PROCESSING)
            ->where('sms_status', Order::SMS_WAITING)
            ->whereNotNull('provider_reference')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '<=', now()->addSeconds(30)))
            ->get();

        foreach ($orders as $order) {
            $purchase->poll($order);
        }
    }

    public function cancelOrder(int $orderId, NumberPurchaseService $purchase): void
    {
        $order = $this->ownedOrder($orderId);

        try {
            $purchase->cancel(auth()->user(), $order);
            session()->flash('success', 'Activation cancelled and your payment was refunded.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function ownedOrder(int $orderId): Order
    {
        return Order::query()
            ->where('user_id', auth()->id())
            ->whereKey($orderId)
            ->firstOrFail();
    }

    public function render()
    {
        $query = NumberService::query()->forCatalog()
            ->when($this->country, fn ($q) => $q->where('country_code', $this->country))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', "%{$this->search}%")
                        ->orWhere('country_name', 'like', "%{$this->search}%")
                        ->orWhere('category', 'like', "%{$this->search}%");
                });
            });

        $query = match ($this->sort) {
            'price_desc' => $query->orderByDesc('price'),
            'price_asc' => $query->orderBy('price'),
            'eta' => $query->orderBy('eta_seconds'),
            default => $query->orderByDesc('popularity_score')->orderByDesc('popular')->orderBy('sort_order')->orderBy('name'),
        };

        $favorited = Favorite::query()
            ->where('user_id', auth()->id())
            ->pluck('number_service_id')
            ->flip();

        $activeOrders = Order::numbers()
            ->where('user_id', auth()->id())
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_COMPLETED])
            ->with(['orderable'])
            ->orderByDesc('id')
            ->get();

        $history = Order::numbers()
            ->where('user_id', auth()->id())
            ->whereIn('status', [Order::STATUS_EXPIRED, Order::STATUS_FAILED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->with(['orderable'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('livewire.services.numbers', [
            'services' => $query->paginate(12),
            'countries' => NumberService::query()->visible()->select('country_code', 'country_name')->distinct()->orderBy('country_name')->get(),
            'categories' => NumberService::query()->visible()->distinct()->orderBy('category')->pluck('category'),
            'favorited' => $favorited,
            'activeOrders' => $activeOrders,
            'history' => $history,
            'sorts' => [
                'popular' => 'Most popular',
                'price_asc' => 'Price: Low to High',
                'price_desc' => 'Price: High to Low',
                'eta' => 'Fastest delivery',
            ],
        ]);
    }
}
