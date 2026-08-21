<div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">SMS verification numbers</h1>
            <p class="text-sm text-slate-500">Buy a virtual number, receive the code, done — fully automated</p>
        </div>
        <div class="flex rounded-xl bg-slate-100 p-1">
            <button type="button" wire:click="showTab('browse')"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $tab === 'browse' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Browse numbers
            </button>
            <button type="button" wire:click="showTab('mine')"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $tab === 'mine' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                My numbers
                @if ($activeOrders->filter(fn ($o) => $o->isWaitingSms())->count() > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-600 px-1.5 text-xs font-bold text-white">
                        {{ $activeOrders->filter(fn ($o) => $o->isWaitingSms())->count() }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    @if ($tab === 'browse')
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative sm:max-w-xs sm:flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search service or country..." class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </div>
                <select wire:model.live="country" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <option value="">All countries</option>
                    @foreach ($countries as $c)
                        <option value="{{ $c->country_code }}">{{ $c->flag() }} {{ $c->country_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="category" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="sort" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($services as $service)
                    <x-card>
                        <div class="flex items-start justify-between">
                            <span class="text-2xl leading-none">{{ $service->flag() }}</span>
                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                <x-badge tone="slate">{{ ucfirst($service->category) }}</x-badge>
                                @if ($service->featured || $service->popular)
                                    <x-badge tone="amber">
                                        <x-icon name="star" class="h-3 w-3" />
                                        {{ $service->popular ? 'Popular' : 'Featured' }}
                                    </x-badge>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 flex items-start justify-between gap-2">
                            <div>
                                <p class="text-lg font-bold tracking-tight text-slate-900">{{ $service->name }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $service->country_name }}</p>
                            </div>
                            <button type="button" wire:click="toggleFavorite({{ $service->id }})" class="rounded-lg p-1.5 text-slate-300 transition hover:text-rose-500" aria-label="Toggle favorite">
                                <x-icon name="heart" class="h-5 w-5 {{ isset($favorited[$service->id]) ? 'fill-rose-500 text-rose-500' : '' }}" />
                            </button>
                        </div>

                        <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
                            @if ($service->eta)
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="clock" class="h-3.5 w-3.5" />
                                    ~{{ $service->eta }}
                                </span>
                            @endif
                            @if ($service->stock !== null)
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="hashtag" class="h-3.5 w-3.5" />
                                    {{ number_format($service->stock) }} in stock
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                            <p class="text-lg font-bold text-brand-700">
                                {{ \App\Support\Money::format($service->price, 2) }}
                            </p>
                            <x-button size="sm" wire:click="confirm({{ $service->id }})">
                                Buy number
                            </x-button>
                        </div>
                    </x-card>
                @empty
                    <div class="col-span-full">
                        <x-card>
                            <div class="py-10 text-center">
                                <x-icon name="phone" class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 text-sm font-medium text-slate-500">No numbers match your filters</p>
                            </div>
                        </x-card>
                    </div>
                @endforelse
            </div>

            @if ($services->hasPages())
                <div>{{ $services->links() }}</div>
            @endif
        </div>

        @if ($confirmingId !== null)
            @php $confirming = $services->firstWhere('id', $confirmingId); @endphp
            @if ($confirming)
                <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-sm sm:items-center" wire:click.self="dismiss">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Confirm purchase</h3>
                                <p class="mt-1 text-sm text-slate-500">A number will be reserved for you and the code delivered as an SMS.</p>
                            </div>
                            <button type="button" wire:click="dismiss" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" aria-label="Close">
                                <x-icon name="x" class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Service</span>
                                <span class="font-semibold text-slate-900">{{ $confirming->displayName() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Category</span>
                                <span class="font-semibold text-slate-900">{{ ucfirst($confirming->category) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-2">
                                <span class="text-slate-500">Total</span>
                                <span class="text-lg font-bold text-brand-700">{{ \App\Support\Money::format($confirming->price, 2) }}</span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">The fee is only charged when the number is reserved. If no SMS arrives within the reservation window you get a full refund.</p>

                        <div class="mt-5 flex items-center justify-end gap-3">
                            <x-button variant="ghost" wire:click="dismiss">Cancel</x-button>
                            <x-button wire:click="openPin({{ $confirming->id }})" wire:loading.attr="disabled" wire:target="openPin({{ $confirming->id }})">
                                Pay {{ \App\Support\Money::format($confirming->price, 2) }}
                            </x-button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @else
        <div class="space-y-5" wire:poll.15s="refreshDue">            @forelse ($activeOrders as $order)
                <x-card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="text-3xl leading-none">{{ $order->orderable?->flag() ?? '🏳️' }}</span>
                            <div>
                                <p class="text-base font-bold text-slate-900">{{ $order->title }}</p>
                                <p class="text-sm text-slate-500">{{ $order->orderable?->country_name ?? ($order->meta['country'] ?? '') }} · Order #{{ $order->reference }}</p>

                                @if ($order->sms_status === \App\Models\Order::SMS_RECEIVED)
                                    <div class="mt-3 space-y-2">
                                        <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                                            <x-icon name="check" class="h-5 w-5 text-emerald-600" />
                                            <div>
                                                <p class="text-xs font-medium text-emerald-700">Verification code received</p>
                                                <p class="text-xl font-bold tracking-[0.25em] text-emerald-900">{{ $order->sms_code }}</p>
                                            </div>
                                        </div>
                                        <div x-data="{ copied: false }" class="flex flex-wrap items-center gap-2 text-sm">
                                            <span class="font-mono text-slate-700">{{ $order->phone_number }}</span>
                                            <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" x-on:click="navigator.clipboard.writeText('{{ $order->phone_number }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                                <x-icon x-show="!copied" name="copy" class="h-4 w-4" />
                                                <x-icon x-show="copied" name="check" class="h-4 w-4 text-emerald-600" />
                                            </button>
                                        </div>
                                    </div>
                                @elseif ($order->isWaitingSms())
                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        @if ($order->phone_number)
                                            <div x-data="{ copied: false }" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                                <span class="font-mono text-slate-700">{{ $order->phone_number }}</span>
                                                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" x-on:click="navigator.clipboard.writeText('{{ $order->phone_number }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                                    <x-icon x-show="!copied" name="copy" class="h-4 w-4" />
                                                    <x-icon x-show="copied" name="check" class="h-4 w-4 text-emerald-600" />
                                                </button>
                                            </div>
                                        @endif
                                        @if ($order->expires_at)
                                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800"
                                                  x-data="{ expiresAt: {{ $order->expires_at->timestamp }}, remaining: Math.max(0, {{ $order->expires_at->timestamp }} - Math.floor(Date.now() / 1000)) }"
                                                  x-init="let t = setInterval(() => { remaining = Math.max(0, {{ $order->expires_at->timestamp }} - Math.floor(Date.now() / 1000)); if (remaining <= 0) clearInterval(t); }, 1000)">
                                                <x-icon name="clock" class="h-4 w-4" />
                                                Expires in
                                                <span x-text="String(Math.floor(remaining / 60)).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0')"></span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <x-button size="sm" variant="outline" wire:click="refresh({{ $order->id }})" wire:loading.attr="disabled" wire:target="refresh({{ $order->id }})">
                                            <x-icon name="arrow-right" class="h-4 w-4" />
                                            Check SMS now
                                        </x-button>
                                        <x-button size="sm" variant="ghost" wire:click="cancelOrder({{ $order->id }})" wire:confirm="Cancel this activation and refund your wallet?">
                                            Cancel & refund
                                        </x-button>
                                    </div>
                                @else
                                    <p class="mt-3 text-sm text-slate-500">Waiting for the provider to assign a number.</p>
                                @endif
                            </div>
                        </div>
                        <x-badge :tone="$order->status === \App\Models\Order::STATUS_COMPLETED ? 'sky' : ($order->isWaitingSms() ? 'amber' : 'slate')" class="shrink-0">
                            {{ $order->sms_status === \App\Models\Order::SMS_RECEIVED ? 'Completed' : 'Waiting for SMS' }}
                        </x-badge>
                    </div>
                </x-card>
            @empty
                <x-card>
                    <div class="py-12 text-center">
                        <x-icon name="phone" class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-3 text-sm font-medium text-slate-500">You have no active numbers</p>
                        <div class="mt-4">
                            <x-button variant="outline" wire:click="showTab('browse')">Browse numbers</x-button>
                        </div>
                    </div>
                </x-card>
            @endforelse

            @if ($history->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">Recent activations</h2>
                    <x-card>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($history as $order)
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $order->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $order->created_at->diffForHumans() }} · #{{ $order->reference }}</p>
                                    </div>
                                    <x-badge :tone="$order->status === \App\Models\Order::STATUS_EXPIRED ? 'amber' : ($order->status === \App\Models\Order::STATUS_FAILED ? 'rose' : 'slate')">
                                        {{ ucfirst($order->status) }}
                                    </x-badge>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                </div>
            @endif
        </div>
    @endif

    @include('livewire.partials.transaction-pin')
</div>
