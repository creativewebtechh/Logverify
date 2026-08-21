<?php

namespace App\Livewire\Dashboard;

use App\Models\Notification;
use App\Models\Number;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tool;
use App\Services\WalletService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    #[Computed]
    public function wallet()
    {
        return auth()->user()->wallet?->refresh() ?? app(WalletService::class)->getOrCreateWallet(auth()->user());
    }

    #[Computed]
    public function numbersCount(): int
    {
        return Number::where('status', 'available')->count();
    }

    #[Computed]
    public function servicesCount(): int
    {
        return Service::where('status', 'active')->count();
    }

    #[Computed]
    public function toolsCount(): int
    {
        return Tool::where('status', 'active')->count();
    }

    #[Computed]
    public function services()
    {
        return Cache::remember('dashboard.services', 300, function () {
            return Service::query()
                ->where('status', 'active')
                ->orderBy('platform')
                ->get()
                ->groupBy('platform');
        });
    }

    public function render()
    {
        $user = auth()->user();
        $user->loadCount(['orders', 'referrals']);

        return view('livewire.dashboard.index', [
            'stats' => [
                'balance' => (float) ($user->wallet?->balance ?? 0),
                'orders' => $user->orders_count,
                'referrals' => $user->referrals_count,
                'unread' => Notification::unreadCountFor($user->id),
            ],
            'recentTransactions' => $user->transactions()->latest()->take(5)->get(),
            'announcement' => Setting::get('announcement'),
        ]);
    }
}
