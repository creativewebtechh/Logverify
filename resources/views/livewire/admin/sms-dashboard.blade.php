<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">SMS Dashboard</h1>
            <p class="text-sm text-slate-500">Virtual numbers, activations, waiting SMS and refunds at a glance</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select wire:model="days" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @foreach ([7, 14, 30, 90, 180] as $option)
                    <option value="{{ $option }}">Last {{ $option }} days</option>
                @endforeach
            </select>
            <x-button type="button" variant="secondary" size="sm" wire:click="syncAll" wire:loading.attr="disabled" wire:target="syncAll">Sync all providers</x-button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @elseif (isset($messages['sync']))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ $messages['sync'] }}
        </div>
    @endif

    @php
        $statusTone = static fn (string $status) => match ($status) {
            'completed' => 'brand',
            'processing' => 'sky',
            'failed' => 'rose',
            'refunded' => 'amber',
            'expired' => 'amber',
            'cancelled' => 'slate',
            default => 'amber',
        };
        $smsTone = static fn (?string $status) => match ($status) {
            'received' => 'brand',
            'waiting' => 'amber',
            'expired' => 'amber',
            'cancelled' => 'slate',
            'no_sms' => 'rose',
            default => 'slate',
        };
        $healthTone = static fn (?string $status) => match ($status) {
            'healthy' => 'brand',
            'degraded' => 'amber',
            'unhealthy' => 'rose',
            default => 'slate',
        };
    @endphp

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 transition hover:shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Catalog items</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="sparkles" class="h-4.5 w-4.5" /></span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($servicesCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($visibleServicesCount) }} visible · {{ number_format($countriesCount) }} countries</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-5 transition hover:shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Waiting for SMS</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="clock" class="h-4.5 w-4.5" /></span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($waitingNow) }}</p>
            <p class="mt-1 text-xs text-slate-500">right now</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-5 transition hover:shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Expired activations</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="arrow-down-left" class="h-4.5 w-4.5" /></span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($expiredCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\Money::format($refundedValue) }} auto-refunded</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-5 transition hover:shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Numbers orders</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="phone" class="h-4.5 w-4.5" /></span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($ordersThisMonth) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\Money::format($revenueThisMonth) }} revenue this month</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-1">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Orders by status</h2>
                <span class="text-xs text-slate-400">last {{ $days }} days</span>
            </div>
            <div class="mt-5 space-y-4">
                @forelse ($statuses as $status => $row)
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <x-badge :tone="$statusTone((string) $status)">{{ ucfirst((string) $status) }}</x-badge>
                            <span class="text-sm font-semibold text-slate-900">{{ number_format((int) $row->c) }}</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $statusTotal > 0 ? round($row->c / $statusTotal * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-slate-500">No number orders in this period.</p>
                @endforelse
            </div>
        </x-card>

        <x-card class="lg:col-span-2">
            <h2 class="text-sm font-bold text-slate-900">Recent activations</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($recentOrders as $order)
                    <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">
                                {{ $order->phone_number ?? '—' }}
                                <span class="text-slate-400">·</span>
                                {{ $order->title }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $order->user?->name ?? 'Guest' }}
                                <span class="ml-1">·</span>
                                <span class="ml-1 font-mono">{{ \App\Support\Money::format($order->total) }}</span>
                                @if ($order->sms_code)
                                    <span class="ml-2 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-600">{{ $order->sms_code }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-badge :tone="$smsTone($order->sms_status)">{{ ucfirst((string) $order->sms_status) }}</x-badge>
                            <span class="text-xs text-slate-400">{{ $order->created_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No activations yet.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card class="lg:col-span-1">
            <h2 class="text-sm font-bold text-slate-900">Top services</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($topServices as $index => $service)
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ $index + 1 }}</span>
                        <p class="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">{{ $service->displayName() }}</p>
                        <x-badge tone="brand">{{ number_format((int) $service->orders_count) }} orders</x-badge>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No services yet.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card class="lg:col-span-2">
            <h2 class="text-sm font-bold text-slate-900">Provider health</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (['healthy' => 'brand', 'degraded' => 'amber', 'unhealthy' => 'rose', 'unknown' => 'slate'] as $key => $tone)
                    @if (isset($providerHealth[$key]))
                        <x-badge :tone="$tone">{{ ucfirst($key) }}: {{ (int) $providerHealth[$key]->c }}</x-badge>
                    @endif
                @endforeach
            </div>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($providersHealthList as $provider)
                    <li class="flex items-center gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $provider->name }}</p>
                            <p class="text-xs text-slate-500">
                                @if ($provider->success_rate !== null)
                                    <span>{{ number_format((float) $provider->success_rate, 2) }}% success</span>
                                @endif
                                @if ($provider->response_time_ms !== null)
                                    <span class="ml-1">· {{ (int) $provider->response_time_ms }} ms</span>
                                @endif
                                @if ($provider->last_synced_at)
                                    <span class="ml-1">· synced {{ $provider->last_synced_at->diffForHumans() }}</span>
                                @endif
                            </p>
                        </div>
                        <x-badge :tone="$healthTone($provider->health_status)">{{ ucfirst((string) $provider->health_status) }}</x-badge>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No numbers providers configured.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card class="lg:col-span-3">
            <h2 class="text-sm font-bold text-slate-900">Recent price changes</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($priceChanges as $change)
                    <li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $change->numberService?->displayName() ?? 'Service #'.$change->number_service_id }}</p>
                            <p class="text-xs text-slate-500">
                                <span class="font-mono">{{ \App\Support\Money::format($change->old_price, 4) }}</span>
                                <span class="text-slate-400">→</span>
                                <span class="font-mono font-semibold text-slate-700">{{ \App\Support\Money::format($change->new_price, 4) }}</span>
                                @if ($change->reason)
                                    <span class="ml-2 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">{{ $change->reason }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400">{{ $change->created_at?->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No price changes recorded.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>
