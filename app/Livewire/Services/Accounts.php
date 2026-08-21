<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\ConfirmsTransactionPin;
use App\Models\Account;
use App\Models\Notification;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\WithPagination;

class Accounts extends Component
{
    use ConfirmsTransactionPin;
    use WithPagination;

    public string $platform = '';

    public string $search = '';

    public string $sort = 'price_asc';

    public ?int $pendingAccountId = null;

    protected $paginationTheme = 'tailwind';

    public function updatedPlatform(): void
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

    public function buy(int $accountId): void
    {
        $account = Account::where('id', $accountId)->where('status', 'available')->firstOrFail();

        $this->pendingAccountId = $accountId;

        $this->openPinModal((float) $account->price);
    }

    public function confirmPurchase(OrderService $orders): void
    {
        if (! $this->verifyPin()) {
            return;
        }

        $account = Account::where('id', $this->pendingAccountId)->where('status', 'available')->first();
        $this->pendingAccountId = null;

        if ($account === null) {
            session()->flash('error', 'This account is no longer available.');

            return;
        }

        try {
            $order = $orders->buyAccount(auth()->user(), $account);
            Notification::notify(
                auth()->id(),
                'Account purchased',
                'Your account '.$account->title.' has been delivered. Order #'.$order->reference.'.',
                'success',
                route('orders')
            );
            session()->flash('success', 'Account purchased successfully. Login details are in your order.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = Account::where('status', 'available')
            ->when($this->platform, fn ($q) => $q->where('platform', $this->platform))
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('platform', 'like', "%{$this->search}%");
                });
            });

        $query = match ($this->sort) {
            'price_desc' => $query->orderByDesc('price'),
            'title' => $query->orderBy('title')->orderBy('price'),
            default => $query->orderBy('price'),
        };

        return view('livewire.services.accounts', [
            'accounts' => $query->paginate(12),
            'platforms' => Account::where('status', 'available')->distinct()->orderBy('platform')->pluck('platform'),
            'sorts' => [
                'price_asc' => 'Price: Low to High',
                'price_desc' => 'Price: High to Low',
                'title' => 'Title A–Z',
            ],
        ]);
    }
}
