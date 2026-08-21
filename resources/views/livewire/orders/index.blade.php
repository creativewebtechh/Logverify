<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">My orders</h1>
            <p class="text-sm text-slate-500">Track your numbers, tools and boost orders</p>
        </div>
        <select wire:model.live="status" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
            <option value="">All statuses</option>
            @foreach (['pending', 'paid', 'processing', 'completed', 'failed', 'refunded'] as $s)
                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    <x-card :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                <li class="flex items-center gap-4 px-5 py-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon :name="$order->orderable_type === \App\Models\Number::class ? 'phone' : ($order->orderable_type === \App\Models\Tool::class ? 'sparkles' : ($order->orderable_type === \App\Models\Account::class ? 'at' : 'bolt'))" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $order->title }}</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ $order->reference }} &middot; {{ $order->created_at?->format('M j, Y g:i A') }}
                            @if ($order->quantity > 1) &middot; Qty {{ $order->quantity }} @endif
                        </p>
                        @if ($order->meta['target'] ?? null)
                            <p class="mt-0.5 truncate text-xs text-brand-600">Target: {{ $order->meta['target'] }}</p>
                        @endif
                        @if ($order->meta['provider_number'] ?? null)
                            <p class="mt-0.5 truncate text-xs font-semibold text-brand-600">Delivered number: {{ $order->meta['provider_number'] }}</p>
                        @endif
                        @if ($order->meta['provider_reference'] ?? null)
                            <p class="mt-0.5 truncate text-xs text-slate-400">Provider ref: {{ $order->meta['provider_reference'] }}</p>
                        @endif
                        @if ($order->meta['provider_error'] ?? null)
                            <p class="mt-0.5 truncate text-xs text-rose-500">Provider error — funds refunded</p>
                        @endif
                        @if ($account = $order->meta['account'] ?? null)
                            <div class="mt-2 rounded-xl bg-slate-50 p-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Account login details</p>
                                    <button type="button" x-on:click="navigator.clipboard.writeText(@js(json_encode($account)))"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                                        <x-icon name="copy" class="h-3.5 w-3.5" /> Copy all
                                    </button>
                                </div>
                                <dl class="mt-2 space-y-1 text-xs">
                                    @foreach (['username' => 'Username', 'password' => 'Password', 'email' => 'Email', 'phone' => 'Phone', 'two_fa' => '2FA / OTP', 'backup_codes' => 'Backup codes', 'proxy' => 'Proxy'] as $key => $label)
                                        @if (($account[$key] ?? null) !== null)
                                            <div class="flex items-start justify-between gap-3">
                                                <dt class="shrink-0 text-slate-400">{{ $label }}</dt>
                                                <dd class="truncate font-mono font-semibold text-slate-900">{{ $account[$key] }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                    @if (($account['cookies'] ?? null) !== null)
                                        <div class="pt-1">
                                            <p class="text-slate-400">Cookies</p>
                                            <p class="mt-0.5 max-h-24 overflow-y-auto whitespace-pre-wrap break-all rounded-lg bg-white p-2 font-mono text-[11px] text-slate-700 ring-1 ring-slate-100">{{ $account['cookies'] }}</p>
                                        </div>
                                    @endif
                                    @if (($account['info'] ?? null) !== null)
                                        <div class="pt-1">
                                            <p class="text-slate-400">Extra info</p>
                                            <p class="mt-0.5 whitespace-pre-wrap break-all text-slate-600">{{ $account['info'] }}</p>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900">
                            {{ \App\Support\Money::format($order->total) }}
                        </p>
                        <x-badge :tone="$order->status === 'completed' ? 'brand' : ($order->status === 'paid' ? 'sky' : ($order->status === 'processing' ? 'amber' : ($order->status === 'failed' || $order->status === 'refunded' ? 'rose' : 'slate')))">
                            {{ ucfirst($order->status) }}
                        </x-badge>
                    </div>
                </li>
            @empty
                <li class="px-5 py-16 text-center">
                    <x-icon name="receipt" class="mx-auto h-10 w-10 text-slate-300" />
                    <p class="mt-3 text-sm font-medium text-slate-500">No orders yet</p>
                    <p class="mt-1 text-xs text-slate-400">Buy a number, tool or boost to get started</p>
                </li>
            @endforelse
        </ul>

        @if ($orders->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $orders->links() }}
            </div>
        @endif
    </x-card>
</div>
