@props([
    'balance',
    'userName',
])

<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-lg shadow-brand-900/20 sm:p-8">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-24 right-32 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -left-10 bottom-8 h-40 w-40 rounded-full bg-white/[0.07]"></div>
        <div class="absolute inset-0 opacity-[0.35]"
             style="background-image: radial-gradient(circle at 1px 1px, rgb(255 255 255 / 0.18) 1px, transparent 0); background-size: 22px 22px;"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
    </div>

    <div class="relative">
        <div class="flex flex-wrap items-center gap-2.5">
            <p class="text-xs font-semibold uppercase tracking-widest text-brand-100">Available balance</p>
            <span class="rounded-full border border-white/25 bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold backdrop-blur">
                {{ config('app.currency') }}
            </span>
        </div>
        <p class="mt-2 truncate text-4xl font-bold tracking-tight sm:text-5xl">
            {{ \App\Support\Money::format($balance) }}
        </p>

        <div class="mt-8 flex flex-col gap-4 border-t border-white/15 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs text-brand-200">Account holder</p>
                <p class="mt-0.5 truncate text-sm font-semibold">{{ $userName }}</p>
            </div>
            <a href="{{ route('wallet') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-accent-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-950/30 transition hover:bg-accent-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-[0.98]">
                <x-icon name="plus" class="h-4 w-4" />
                Fund Wallet
            </a>
        </div>
    </div>
</div>
