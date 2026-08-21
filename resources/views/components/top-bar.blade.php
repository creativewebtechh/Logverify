@php
    $titles = [
        'dashboard' => 'Dashboard',
        'wallet' => 'Fund Wallet',
        'wallet.transactions' => 'Transactions',
        'numbers' => 'Buy Number',
        'accounts' => 'Accounts',
        'tools' => 'Buy Tools',
        'boost' => 'Boost',
        'orders' => 'My Orders',
        'referrals' => 'Referrals',
        'notifications' => 'Notifications',
    ];
    $currentTitle = $titles[request()->route()?->getName()] ?? 'Dashboard';
@endphp

<div class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-100 bg-white/90 px-4 backdrop-blur-md sm:px-6">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 lg:hidden" @click="$dispatch('toggle-mobile')" aria-label="Toggle menu">
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <div class="lg:hidden">
            <a href="{{ route('dashboard') }}" class="flex items-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                <x-logo variant="wide" class="h-7 w-auto" />
            </a>
        </div>

        <h1 class="hidden truncate text-lg font-bold tracking-tight text-slate-900 lg:block">{{ $currentTitle }}</h1>
    </div>

    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
        <form action="{{ route('numbers') }}" method="GET" class="relative hidden md:block" role="search">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" aria-label="Search numbers" placeholder="Search numbers..."
                   class="w-44 rounded-xl border-0 bg-slate-50 py-2 pl-9 pr-3 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 lg:w-56">
        </form>

        <a href="{{ route('wallet') }}" class="flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-1.5 ring-1 ring-inset ring-brand-600/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
            <span class="relative flex h-2 w-2">
                <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500"></span>
            </span>
            <span class="text-sm font-semibold text-brand-700">
                {{ \App\Support\Money::format(auth()->user()->wallet?->balance ?? 0) }}
            </span>
        </a>

        <a href="{{ route('notifications') }}" class="relative rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600" aria-label="Notification bell">
            <x-icon name="bell" class="h-5 w-5" />
            @if (\App\Models\Notification::unreadCountFor(auth()->id()) > 0)
                <span class="absolute right-1.5 top-1.5 flex h-2 w-2">
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500 ring-2 ring-white"></span>
                </span>
            @endif
        </a>

        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="flex items-center rounded-full p-0.5 ring-2 ring-transparent transition hover:ring-brand-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600" @click="open = !open" aria-label="Account menu">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            </button>

            <div x-cloak x-show="open" x-transition
                 class="absolute right-0 z-30 mt-2 w-56 origin-top-right rounded-2xl border border-slate-100 bg-white p-1.5 shadow-sm">
                <div class="border-b border-slate-100 px-3 pb-2 pt-2">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                </div>
                <div class="pt-1.5">
                    <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        Dashboard
                    </a>
                    <a href="{{ route('orders') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        My Orders
                    </a>
                    <a href="{{ route('security') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        Account Security
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                            Admin Panel
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="pt-1.5">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                            <x-icon name="logout" class="h-4 w-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
