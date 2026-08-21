<div>
    <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

        @if (! empty($announcement))
            <div class="flex items-start gap-3 rounded-2xl border border-brand-600/15 bg-brand-50 px-4 py-3 text-sm text-brand-900">
                <x-icon name="megaphone" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                <div>
                    <p class="font-semibold">Announcement</p>
                    <p class="mt-0.5">{{ $announcement }}</p>
                </div>
            </div>
        @endif

        {{-- Balance card --}}
        <x-balance-card
            :balance="$this->wallet->balance"
            :user-name="auth()->user()->name"
        />

        {{-- Quick actions --}}
        <x-quick-actions :actions="[
            ['route' => 'wallet', 'label' => 'Fund', 'icon' => 'wallet'],
            ['route' => 'numbers', 'label' => 'Buy Number', 'icon' => 'phone'],
            ['route' => 'accounts', 'label' => 'Accounts', 'icon' => 'at'],
            ['route' => 'boost', 'label' => 'Boost', 'icon' => 'bolt'],
            ['route' => 'tools', 'label' => 'Buy Tools', 'icon' => 'sparkles'],
            ['route' => 'orders', 'label' => 'Orders', 'icon' => 'cart'],
        ]" />

        {{-- Recent activity --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Recent activity</h2>
                <a href="{{ route('wallet.transactions') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
            </div>

            <x-card :padding="false">
                @forelse ($recentTransactions as $t)
                    <div class="flex items-center gap-3 px-5 py-3 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $t->amount >= 0 ? 'bg-brand-50 text-brand-600' : 'bg-rose-50 text-rose-600' }}">
                            <x-icon :name="$t->amount >= 0 ? 'arrow-down-left' : 'arrow-up-right'" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $t->description ?? $t->type }}</p>
                            <p class="text-xs text-slate-500">{{ $t->created_at->diffForHumans() }} &middot; {{ $t->type }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold {{ $t->amount >= 0 ? 'text-brand-600' : 'text-rose-600' }}">
                            {{ $t->amount >= 0 ? '+' : '−' }}{{ \App\Support\Money::format(abs($t->amount)) }}
                        </p>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">No activity yet. Your wallet transactions will appear here.</p>
                @endforelse
            </x-card>
        </div>

        {{-- All services grid --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">All services</h2>
                <a href="{{ route('boost') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
            </div>

            @if ($this->services->isEmpty())
                <x-card>
                    <p class="text-sm text-slate-500">No services available yet. Check back soon.</p>
                </x-card>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                    @foreach ($this->services as $platform => $items)
                        @foreach ($items as $service)
                            <a href="{{ route('boost', ['service' => $service->id]) }}"
                               class="group flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-4 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-sm">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                    <x-icon :name="$service->icon ?: 'bolt'" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $service->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ ucfirst($service->platform) }} &middot; from {{ \App\Support\Money::format($service->price_per_unit) }}
                                    </p>
                                </div>
                                <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500" />
                            </a>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
