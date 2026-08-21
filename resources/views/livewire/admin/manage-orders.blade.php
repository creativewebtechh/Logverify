<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Orders</h1>
            <p class="text-sm text-slate-500">Process and manage customer orders</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search ref, customer..."
                       class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:w-56">
            </div>
            <select wire:model.live="status" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All statuses</option>
                @foreach (['pending', 'paid', 'processing', 'completed', 'failed', 'refunded', 'expired'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select wire:model.live="channel" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All channels</option>
                <option value="numbers">Numbers</option>
                <option value="boost">Boost</option>
                <option value="accounts">Accounts</option>
                <option value="tools">Tools</option>
            </select>
            <input wire:model.live="from" type="date"
                   class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
            <input wire:model.live="to" type="date"
                   class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-900">{{ $order->title }}</p>
                                <p class="text-xs text-slate-500">{{ $order->reference }}</p>
                                @if ($order->meta['target'] ?? null)
                                    <p class="text-xs text-brand-600">Target: {{ $order->meta['target'] }}</p>
                                @endif
                                @if ($order->isNumber())
                                    <p class="text-xs text-slate-500">
                                        @if ($order->phone_number)
                                            <span class="font-mono font-semibold text-slate-700">{{ $order->phone_number }}</span>
                                        @endif
                                        @if ($order->sms_status)
                                            <span class="ml-1 text-slate-400">SMS: {{ $order->sms_status }}</span>
                                        @endif
                                        @if ($order->sms_code)
                                            <span class="ml-1 rounded bg-brand-50 px-1.5 py-0.5 font-mono font-semibold text-brand-700">{{ $order->sms_code }}</span>
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-slate-900">{{ $order->user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->user?->email }}</p>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ \App\Support\Money::format($order->total) }}</td>
                            <td class="px-5 py-3">
                                <x-badge :tone="$order->status === 'completed' ? 'brand' : ($order->status === 'paid' ? 'sky' : ($order->status === 'processing' ? 'amber' : ($order->status === 'refunded' ? 'rose' : 'slate')))">
                                    {{ ucfirst($order->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $order->created_at?->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="view({{ $order->id }})"
                                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Details</button>
                                    @if ($order->status !== 'completed')
                                        <button type="button" wire:click="setStatus({{ $order->id }}, 'completed')" wire:confirm="Mark this order completed?" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">Complete</button>
                                    @endif
                                    @if ($order->status !== 'processing' && $order->status !== 'completed')
                                        <button type="button" wire:click="setStatus({{ $order->id }}, 'processing')" class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-200">Process</button>
                                    @endif
                                    @if ($order->status !== 'refunded')
                                        <button type="button" wire:click="setStatus({{ $order->id }}, 'refunded')" wire:confirm="Refund this order to the customer's wallet?" class="rounded-lg bg-rose-100 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-200">Refund</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-icon name="cart" class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 text-sm font-medium text-slate-500">
                                    {{ filled($search) || filled($status) || filled($channel) || filled($from) || filled($to) ? 'No orders match your filters' : 'No orders yet' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $orders->links() }}
            </div>
        @endif
    </x-card>

    @if ($viewingOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeView"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold tracking-tight text-slate-900">{{ $viewingOrder->title }}</h3>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $viewingOrder->reference }}</p>
                    </div>
                    <button type="button" wire:click="closeView" class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Customer</dt>
                        <dd class="mt-0.5 font-semibold text-slate-900">{{ $viewingOrder->user?->name }}</dd>
                        <dd class="text-xs text-slate-500">{{ $viewingOrder->user?->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Total</dt>
                        <dd class="mt-0.5 font-semibold text-slate-900">{{ \App\Support\Money::format($viewingOrder->total) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Status</dt>
                        <dd class="mt-0.5">
                            <x-badge :tone="$viewingOrder->status === 'completed' ? 'brand' : 'amber'">{{ ucfirst($viewingOrder->status) }}</x-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Type</dt>
                        <dd class="mt-0.5">
                            <x-badge :tone="$viewingOrder->isNumber() ? 'brand' : ($viewingOrder->isBoost() ? 'sky' : 'slate')">
                                {{ $viewingOrder->isNumber() ? 'Number' : ($viewingOrder->isBoost() ? 'Boost' : 'Manual') }}
                            </x-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Placed</dt>
                        <dd class="mt-0.5 font-semibold text-slate-900">{{ $viewingOrder->created_at?->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Quantity</dt>
                        <dd class="mt-0.5 font-semibold text-slate-900">{{ $viewingOrder->quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Payment</dt>
                        <dd class="mt-0.5 font-semibold text-slate-900">{{ ucfirst($viewingOrder->payment_method ?? 'wallet') }}</dd>
                    </div>
                </dl>

                @if (filled($viewingOrder->meta))
                    <div class="mt-5">
                        <p class="text-xs font-medium text-slate-500">Provider details</p>
                        <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2 rounded-xl bg-slate-50 p-3 text-xs">
                            @if ($viewingOrder->meta['provider'] ?? null)
                                <div class="col-span-2"><dt class="text-slate-400">Provider</dt><dd class="font-medium text-slate-800">{{ $viewingOrder->meta['provider'] }}</dd></div>
                            @endif
                            @if ($viewingOrder->meta['provider_reference'] ?? null)
                                <div><dt class="text-slate-400">Provider ref</dt><dd class="font-mono font-medium text-slate-800">{{ $viewingOrder->meta['provider_reference'] }}</dd></div>
                            @endif
                            @if ($viewingOrder->meta['provider_number'] ?? null)
                                <div><dt class="text-slate-400">Delivered number</dt><dd class="font-mono font-medium text-slate-800">{{ $viewingOrder->meta['provider_number'] }}</dd></div>
                            @endif
                            @if ($viewingOrder->meta['provider_message'] ?? null)
                                <div class="col-span-2"><dt class="text-slate-400">Provider message</dt><dd class="font-medium text-slate-800">{{ $viewingOrder->meta['provider_message'] }}</dd></div>
                            @endif
                            @if ($viewingOrder->meta['provider_error'] ?? null)
                                <div class="col-span-2"><dt class="text-slate-400">Error</dt><dd class="font-medium text-rose-600">{{ $viewingOrder->meta['provider_error'] }}</dd></div>
                            @endif
                        </dl>
                    </div>
                @endif

                @if ($viewingOrder->isNumber())
                    <div class="mt-5">
                        <p class="text-xs font-medium text-slate-500">SMS activation</p>
                        <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2 rounded-xl bg-slate-50 p-3 text-xs">
                            <div>
                                <dt class="text-slate-400">Number</dt>
                                <dd class="font-mono font-semibold text-slate-800">{{ $viewingOrder->phone_number ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">SMS status</dt>
                                <dd class="font-medium text-slate-800">{{ $viewingOrder->sms_status ?? '—' }}</dd>
                            </div>
                            @if ($viewingOrder->sms_code)
                                <div>
                                    <dt class="text-slate-400">SMS code</dt>
                                    <dd class="font-mono font-semibold text-brand-700">{{ $viewingOrder->sms_code }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-slate-400">Expires</dt>
                                <dd class="font-medium text-slate-800">{{ $viewingOrder->expires_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif

                @if ($viewingOrder->statusHistory->isNotEmpty())
                    <div class="mt-5">
                        <p class="text-xs font-medium text-slate-500">Timeline</p>
                        <ol class="mt-2 space-y-3">
                            @foreach ($viewingOrder->timeline() as $event)
                                <li class="flex items-start gap-3">
                                    <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $event['status'] === 'completed' ? 'bg-brand-500' : 'bg-slate-300' }}"></div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800">
                                            {{ ucfirst($event['status']) }}
                                            @if ($event['note'])
                                                <span class="font-normal text-slate-500">— {{ $event['note'] }}</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-400">{{ $event['created_at']?->format('M j, Y g:i A') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                    @if ($viewingOrder->isNumber())
                        @if (in_array($viewingOrder->status, ['paid', 'processing'], true) && $viewingOrder->sms_status === 'waiting')
                            <x-button type="button" variant="outline" size="sm" wire:click="pollNumber({{ $viewingOrder->id }})">Poll now</x-button>
                            <x-button type="button" variant="outline" size="sm"
                                      x-data="{ code: '' }"
                                      x-on:click="code = window.prompt('Enter the SMS code received:'); if (code !== null && code !== '') { $wire.completeNumber({{ $viewingOrder->id }}, code) }">
                                Mark received
                            </x-button>
                            <x-button type="button" variant="danger" size="sm" wire:click="cancelNumber({{ $viewingOrder->id }})" wire:confirm="Cancel this activation and refund the customer?">Cancel &amp; refund</x-button>
                        @endif
                    @endif
                    @if (! in_array($viewingOrder->status, ['cancelled', 'refunded', 'completed'], true) && ! $viewingOrder->isNumber())
                        <x-button type="button" variant="outline" size="sm" wire:click="cancelOrder({{ $viewingOrder->id }})" wire:confirm="Cancel this order and refund the customer?">Cancel</x-button>
                    @endif
                    @if ($viewingOrder->isRefundable() && $viewingOrder->status !== 'refunded')
                        <x-button type="button" variant="danger" size="sm" wire:click="refundOrder({{ $viewingOrder->id }})" wire:confirm="Refund this order to the customer's wallet?">Refund</x-button>
                    @endif
                    @if ($viewingOrder->isBoost() && $viewingOrder->status === 'failed')
                        <x-button type="button" variant="secondary" size="sm" wire:click="retryOrder({{ $viewingOrder->id }})">Retry</x-button>
                    @endif
                    <x-button type="button" variant="outline" wire:click="closeView">Close</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
