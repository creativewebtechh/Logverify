<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Transactions</h1>
            <p class="text-sm text-slate-500">Your full wallet history</p>
        </div>
        <select wire:model.live="type" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <x-card :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse ($transactions as $t)
                <li class="flex items-center gap-4 px-5 py-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                        {{ $t->amount >= 0 ? 'bg-brand-50 text-brand-600' : 'bg-rose-50 text-rose-600' }}">
                        <x-icon :name="$t->amount >= 0 ? 'trending-up' : 'arrow-right'" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $t->description ?? ucfirst($t->type) }}</p>
                        <p class="text-xs text-slate-500">{{ $t->created_at?->format('M j, Y g:i A') }} &middot; {{ $t->reference }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold {{ $t->amount >= 0 ? 'text-brand-600' : 'text-rose-600' }}">
                            {{ $t->amount >= 0 ? '+' : '−' }}{{ \App\Support\Money::format(abs($t->amount)) }}
                        </p>
                        <div class="mt-1 flex items-center justify-end gap-2">
                            <x-badge :tone="$t->status === 'success' ? 'brand' : ($t->status === 'pending' ? 'amber' : 'rose')">
                                {{ ucfirst($t->status) }}
                            </x-badge>
                            @if ($t->type === \App\Models\Transaction::TYPE_DEPOSIT && $t->reference)
                                <a href="{{ route('wallet.transactions.receipt', $t) }}" target="_blank"
                                   class="text-brand-600 hover:text-brand-700" title="View receipt">
                                    <x-icon name="receipt" class="h-4 w-4" />
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-16 text-center">
                    <x-icon name="wallet" class="mx-auto h-10 w-10 text-slate-300" />
                    <p class="mt-3 text-sm font-medium text-slate-500">No transactions yet</p>
                    <p class="mt-1 text-xs text-slate-400">Your wallet activity will appear here</p>
                </li>
            @endforelse
        </ul>

        @if ($transactions->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>
</div>
