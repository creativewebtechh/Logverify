<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'usersCount' => User::count(),
            'ordersCount' => Order::count(),
            'revenue' => (float) Wallet::sum('total_credited'),
            'transactionsCount' => Transaction::count(),
            'recentOrders' => Order::with('user')->latest()->paginate(6, ['*'], 'orders'),
            'recentUsers' => User::latest()->paginate(6, ['*'], 'users'),
        ]);
    }
}
