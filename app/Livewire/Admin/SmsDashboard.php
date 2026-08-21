<?php

namespace App\Livewire\Admin;

use App\Models\NumberPriceHistory;
use App\Models\NumberService;
use App\Models\Order;
use App\Models\Provider;
use App\Services\Numbers\NumberCatalogSyncService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.admin')]
class SmsDashboard extends Component
{
    public ?int $days = 30;

    /** @var array<string, string> */
    public array $messages = [];

    public function updatedDays(): void
    {
        if ($this->days === null || $this->days < 1) {
            $this->days = 1;
        }
    }

    public function syncAll(): void
    {
        try {
            $results = app(NumberCatalogSyncService::class)->syncAll();
            $bits = collect($results)->pluck('message')->filter()->all();
            $this->messages['sync'] = $bits !== [] ? implode(' — ', $bits) : 'Sync complete.';
        } catch (Throwable $e) {
            $this->messages['sync'] = $e->getMessage();
        }

        session()->flash('success', $this->messages['sync']);
    }

    public function render()
    {
        $since = now()->subDays($this->days);

        $statuses = Order::selectRaw('status, count(*) as c')
            ->where('channel', Order::CHANNEL_NUMBERS)
            ->where('created_at', '>=', $since)
            ->groupBy('status')
            ->orderByDesc('c')
            ->get()
            ->keyBy('status');

        $statusTotal = (int) $statuses->sum('c');

        $waitingNow = Order::numbers()
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING])
            ->where('sms_status', Order::SMS_WAITING)
            ->count();

        $expired = Order::numbers()
            ->where('status', Order::STATUS_EXPIRED)
            ->where('created_at', '>=', $since)
            ->count();

        $refundedValue = (float) Order::numbers()
            ->where('status', Order::STATUS_EXPIRED)
            ->where('created_at', '>=', $since)
            ->sum('total');

        return view('livewire.admin.sms-dashboard', [
            'servicesCount' => NumberService::where('status', NumberService::STATUS_ACTIVE)->count(),
            'visibleServicesCount' => NumberService::where('status', NumberService::STATUS_ACTIVE)->where('hidden', false)->count(),
            'countriesCount' => NumberService::where('status', NumberService::STATUS_ACTIVE)->distinct('country_code')->count('country_code'),
            'activeProviders' => Provider::where('active', true)->where('channel', Provider::CHANNEL_NUMBERS)->get(),
            'ordersThisMonth' => Order::numbers()->where('created_at', '>=', now()->startOfMonth())->count(),
            'revenueThisMonth' => (float) Order::numbers()
                ->where('created_at', '>=', now()->startOfMonth())
                ->whereNotIn('status', [Order::STATUS_REFUNDED, Order::STATUS_CANCELLED, Order::STATUS_EXPIRED])
                ->sum('total'),
            'waitingNow' => $waitingNow,
            'expiredCount' => $expired,
            'refundedValue' => $refundedValue,
            'statuses' => $statuses,
            'statusTotal' => $statusTotal,
            'topServices' => NumberService::withCount('orders')->where('status', NumberService::STATUS_ACTIVE)->orderByDesc('orders_count')->limit(5)->get(),
            'recentOrders' => Order::numbers()->with('user')->latest()->limit(10)->get(),
            'providerHealth' => Provider::select('health_status', DB::raw('count(*) as c'))
                ->where('channel', Provider::CHANNEL_NUMBERS)
                ->groupBy('health_status')
                ->get()
                ->keyBy('health_status'),
            'providersHealthList' => Provider::query()->where('channel', Provider::CHANNEL_NUMBERS)->orderBy('priority')->orderBy('id')->limit(6)->get(),
            'priceChanges' => NumberPriceHistory::with('numberService')->latest()->limit(8)->get(),
        ]);
    }
}
