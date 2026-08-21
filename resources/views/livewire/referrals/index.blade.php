<div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Refer &amp; earn</h1>
        <p class="mt-1 text-sm text-slate-500">
            Earn {{ number_format($stats['commission']) }}% of every deposit made by your referrals.
        </p>
    </div>

    {{-- Referral link --}}
    <x-card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">Your referral link</p>
                <p class="mt-1 truncate text-sm font-semibold text-brand-700" x-data x-text="location.origin + '/register?ref={{ auth()->user()->referral_code }}'"></p>
            </div>
            <div class="flex shrink-0 gap-2" x-data="{ copied: false }">
                <input
                    type="text"
                    readonly
                    value="{{ url('/register') . '?ref=' . auth()->user()->referral_code }}"
                    class="hidden w-64 rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-xs ring-1 ring-inset ring-slate-200 sm:block"
                >
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 active:scale-[0.98]"
                    @click="navigator.clipboard.writeText(location.origin + '/register?ref={{ auth()->user()->referral_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                >
                    <x-icon x-show="!copied" name="copy" class="h-4 w-4" />
                    <x-icon x-show="copied" name="check" class="h-4 w-4" />
                    <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                </button>
            </div>
        </div>

        <div class="mt-6 border-t border-slate-100 pt-5">
            <form wire:submit="invite" class="flex flex-col gap-3 sm:flex-row">
                <input
                    wire:model="email"
                    type="email"
                    placeholder="Friend's email address"
                    class="flex-1 rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                >
                <x-button type="submit" wire:loading.attr="disabled" wire:target="invite">
                    <x-icon name="plus" class="h-4 w-4" />
                    Send invite
                </x-button>
            </form>
            @error('email')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>
    </x-card>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total referrals" :value="$stats['total']" icon="users" tone="brand" />
        <x-stat-card label="Ready to claim" :value="$stats['ready']" icon="clock" tone="amber" />
        <x-stat-card label="Claimed" :value="$stats['claimed']" icon="check" tone="sky" />
        <x-stat-card label="Total earned" :value="\App\Support\Money::format($stats['earned'])" icon="wallet" tone="brand" />
    </div>

    {{-- Referral list --}}
    <x-card :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse ($referrals as $r)
                <li class="flex items-center gap-4 px-5 py-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                        {{ strtoupper(substr($r->referredUser->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $r->referredUser->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $r->referredUser->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900">
                            {{ \App\Support\Money::format($r->bonus_amount) }}
                        </p>
                        <x-badge :tone="$r->status === 'claimed' ? 'brand' : ($r->status === 'ready' ? 'amber' : 'slate')">
                            {{ ucfirst($r->status) }}
                        </x-badge>
                    </div>
                </li>
            @empty
                <li class="px-5 py-16 text-center">
                    <x-icon name="users" class="mx-auto h-10 w-10 text-slate-300" />
                    <p class="mt-3 text-sm font-medium text-slate-500">No referrals yet</p>
                    <p class="mt-1 text-xs text-slate-400">Share your link to start earning commission</p>
                </li>
            @endforelse
        </ul>

        @if ($referrals->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $referrals->links() }}
            </div>
        @endif
    </x-card>
</div>
