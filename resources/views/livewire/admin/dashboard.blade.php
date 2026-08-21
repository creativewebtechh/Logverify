<div class="space-y-6" wire:poll.5s>
    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Overview</h1>
        <p class="text-sm text-slate-500">Platform performance at a glance</p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total users" :value="number_format($usersCount)" icon="users" tone="brand" />
        <x-stat-card label="Total orders" :value="number_format($ordersCount)" icon="cart" tone="sky" />
        <x-stat-card label="Revenue" :value="\App\Support\Money::format($revenue)" icon="wallet" tone="amber" />
        <x-stat-card label="Transactions" :value="number_format($transactionsCount)" icon="receipt" tone="brand" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">Recent orders</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($recentOrders as $order)
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <x-icon name="cart" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $order->title }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $order->user?->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-slate-900">{{ \App\Support\Money::format($order->total) }}</p>
                            <x-badge :tone="$order->status === 'completed' ? 'brand' : ($order->status === 'paid' ? 'sky' : 'amber')">{{ ucfirst($order->status) }}</x-badge>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-500">No orders yet</li>
                @endforelse
            </ul>
            @if ($recentOrders->hasPages())
                <div class="mt-2 border-t border-slate-100 pt-3">
                    {{ $recentOrders->links() }}
                </div>
            @endif
        </x-card>

        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">New users</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($recentUsers as $user)
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ $user->created_at?->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-500">No users yet</li>
                @endforelse
            </ul>
            @if ($recentUsers->hasPages())
                <div class="mt-2 border-t border-slate-100 pt-3">
                    {{ $recentUsers->links() }}
                </div>
            @endif
        </x-card>
    </div>
</div>
