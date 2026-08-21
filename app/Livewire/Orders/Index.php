<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?string $status = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.orders.index', [
            'orders' => Order::with(['orderable'])
                ->where('user_id', auth()->id())
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(15),
        ]);
    }
}
