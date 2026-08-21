<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-2xl border border-brand-600/15 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Transactions</h1>
            <p class="text-sm text-slate-500">All wallet activity across the platform</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <select wire:model.live="status" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All statuses</option>
                @foreach (['pending', 'success', 'failed'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select wire:model.live="type" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All types</option>
                @foreach (['deposit', 'purchase', 'referral_commission', 'refund'] as $t)
                    <option value="{{ $t }}">{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="gateway" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All gateways</option>
                @foreach (['paystack', 'monnify'] as $g)
                    <option value="{{ $g }}">{{ ucfirst($g) }}</option>
                @endforeach
            </select>
            <select wire:model.live="paymentStatus" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All payment statuses</option>
                @foreach (['pending', 'success', 'failed', 'amount_mismatch', 'currency_mismatch'] as $ps)
                    <option value="{{ $ps }}">{{ ucwords(str_replace('_', ' ', $ps)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Reference, gateway ref, name or email..."
                        class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                    <input
                        type="date"
                        wire:model.live="from"
                        class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input
                        type="date"
                        wire:model.live="to"
                        class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                    >
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.transactions.export', ['status' => $status, 'type' => $type, 'gateway' => $gateway, 'paymentStatus' => $paymentStatus, 'search' => $search, 'from' => $from, 'to' => $to]) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-800">
                    Export CSV
                </a>
                <a href="{{ route('admin.transactions.export.excel', ['status' => $status, 'type' => $type, 'gateway' => $gateway, 'paymentStatus' => $paymentStatus, 'search' => $search, 'from' => $from, 'to' => $to]) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                    Export Excel
                </a>
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-card>
            <p class="text-xs font-medium text-slate-500">Total funded</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-brand-600">{{ \App\Support\Money::format((float) $stats['funded']) }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-medium text-slate-500">Pending deposits</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-amber-600">{{ number_format($stats['pending']) }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-medium text-slate-500">Failed deposits</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-rose-600">{{ number_format($stats['failed']) }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-medium text-slate-500">Total deposits</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total']) }}</p>
        </x-card>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transactions as $t)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $t->reference }}</td>
                            <td class="px-5 py-3">
                                <p class="text-slate-900">{{ $t->user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $t->user?->email }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ ucwords(str_replace('_', ' ', $t->type)) }}</td>
                            <td class="px-5 py-3 font-semibold {{ $t->amount >= 0 ? 'text-brand-600' : 'text-rose-600' }}">
                                {{ $t->amount >= 0 ? '+' : '−' }}{{ \App\Support\Money::format(abs($t->amount)) }}
                                @if ($t->fee !== null)
                                    <span class="block text-xs font-medium text-slate-400">fee {{ \App\Support\Money::format($t->fee) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @if ($t->gateway || $t->payment_method)
                                    <p class="capitalize">{{ str_replace('_', ' ', (string) $t->payment_method) }}</p>
                                    <p class="text-xs capitalize text-slate-400">{{ $t->gateway }}</p>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-badge :tone="$t->status === 'success' ? 'brand' : ($t->status === 'pending' ? 'amber' : 'rose')">{{ ucfirst($t->status) }}</x-badge>
                                    @if ($t->type === \App\Models\Transaction::TYPE_DEPOSIT && $t->payment_status)
                                        <x-badge :tone="$t->payment_status === 'success' ? 'brand' : ($t->payment_status === 'pending' ? 'amber' : 'rose')" class="text-[10px]">
                                            {{ ucwords(str_replace('_', ' ', $t->payment_status)) }}
                                        </x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $t->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-col items-start gap-1.5">
                                    <a href="{{ route('wallet.transactions.receipt', $t) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700">
                                        <x-icon name="receipt" class="h-3.5 w-3.5" /> Receipt
                                    </a>
                                    @if ($t->type === \App\Models\Transaction::TYPE_DEPOSIT && $t->status === 'pending' && $t->gateway)
                                        <button wire:click="retryVerification({{ $t->id }})" wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                            <x-icon name="clock" class="h-3.5 w-3.5" /> Retry verify
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">No transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>
</div>
