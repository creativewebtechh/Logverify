<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\ConfirmsTransactionPin;
use App\Models\Notification;
use App\Models\Tool;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\WithPagination;

class Tools extends Component
{
    use ConfirmsTransactionPin;
    use WithPagination;

    public string $category = '';

    public string $search = '';

    public string $sort = 'name_asc';

    public ?int $pendingToolId = null;

    protected $paginationTheme = 'tailwind';

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

    public function buy(int $toolId): void
    {
        $tool = Tool::where('id', $toolId)->where('status', 'active')->firstOrFail();

        $this->pendingToolId = $toolId;

        $this->openPinModal((float) $tool->price);
    }

    public function confirmPurchase(OrderService $orders): void
    {
        if (! $this->verifyPin()) {
            return;
        }

        $tool = Tool::where('id', $this->pendingToolId)->where('status', 'active')->first();
        $this->pendingToolId = null;

        if ($tool === null) {
            session()->flash('error', 'This tool is no longer available.');

            return;
        }

        try {
            $order = $orders->buyTool(auth()->user(), $tool);
            Notification::notify(
                auth()->id(),
                'Tool purchased',
                'You now own '.$tool->name.'. Download link sent to your account. Order #'.$order->reference.'.',
                'success',
                route('orders')
            );
            session()->flash('success', 'Tool purchased successfully.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = Tool::where('status', 'active')
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('category', 'like', "%{$this->search}%");
                });
            });

        $query = match ($this->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderBy('name'),
        };

        return view('livewire.services.tools', [
            'tools' => $query->paginate(12),
            'categories' => Tool::where('status', 'active')->distinct()->orderBy('category')->pluck('category'),
            'sorts' => [
                'name_asc' => 'Name A–Z',
                'price_asc' => 'Price: Low to High',
                'price_desc' => 'Price: High to Low',
            ],
        ]);
    }
}
