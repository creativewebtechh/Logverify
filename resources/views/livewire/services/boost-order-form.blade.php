<div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
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

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Social media boost</h1>
            <p class="text-sm text-slate-500">Pick a platform, choose a service and paste your link to get started</p>
        </div>
        <x-button variant="outline" size="sm" wire:click="refreshCatalog" wire:loading.attr="disabled" wire:target="refreshCatalog">
            Refresh services
        </x-button>
    </div>

    @if ($insufficientBalance)
        <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-800">Insufficient wallet balance</p>
                <p class="text-sm text-amber-700">You need {{ \App\Support\Money::format($charge) }} to place this order, but your balance is {{ \App\Support\Money::format($balance) }}.</p>
            </div>
            <x-button variant="primary" size="sm" href="{{ route('wallet') }}">
                Fund your wallet
            </x-button>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Choose a platform</h2>
                        <p class="mt-0.5 text-xs text-slate-500">We offer services for {{ count($platforms) }} platforms</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-5 lg:grid-cols-6">
                    <button type="button"
                            wire:click="selectPlatform(null)"
                            class="flex flex-col items-center justify-center gap-2 rounded-2xl border p-3 transition
                                {{ $this->platform === null ? 'border-brand-700 bg-brand-700 ring-2 ring-accent-500/70' : 'border-slate-100 bg-white hover:border-brand-200' }}">
                        <span class="{{ $this->platform === null ? 'text-white' : 'text-slate-400' }}">
                            <x-icon name="sparkles" class="h-7 w-7" />
                        </span>
                        <span class="text-xs font-medium {{ $this->platform === null ? 'text-white' : 'text-slate-600' }}">All</span>
                    </button>

                    @foreach ($platforms as $platform)
                        <button type="button"
                                wire:click="selectPlatform('{{ $platform['key'] }}')"
                                class="flex flex-col items-center justify-center gap-2 rounded-2xl border p-3 transition
                                    {{ $this->platform === $platform['key'] ? 'border-brand-700 bg-brand-700 ring-2 ring-accent-500/70' : 'border-slate-100 bg-white hover:border-brand-200' }}">
                            <span class="{{ $this->platform === $platform['key'] ? 'text-white' : 'text-slate-500' }}">
                                <x-brand-icon :name="$platform['icon']" class="h-7 w-7" />
                            </span>
                            <span class="text-xs font-medium {{ $this->platform === $platform['key'] ? 'text-white' : 'text-slate-600' }}">{{ $platform['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Choose a service</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            @if ($this->platform)
                                Showing services for {{ \App\Services\Providers\ProviderCatalog::platformLabel($this->platform) }}
                            @else
                                Showing all services
                            @endif
                        </p>
                    </div>
                    <div class="relative sm:w-64">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search services..."
                               class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-9 pr-3.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="block text-sm font-medium text-slate-700">Category</label>
                        <select wire:model.live="category" id="category"
                                class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="">All categories</option>
                            @foreach ($categories as $categoryName)
                                <option value="{{ $categoryName }}">{{ $categoryName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="provider_service_id" class="block text-sm font-medium text-slate-700">Service</label>
                        <select wire:model.live="provider_service_id" id="provider_service_id"
                                class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="">Choose a service…</option>
                            @foreach ($services as $service)
                                <option value="{{ $service['provider_service_id'] }}">
                                    {{ $service['name'] }} — {{ number_format($service['min']) }}–{{ number_format($service['max']) }}@if (! empty($service['avg_time'])) · {{ $service['avg_time'] }}@endif
                                </option>
                            @endforeach
                        </select>
                        @if (count($services) === 0)
                            <p class="mt-1.5 text-xs font-medium text-slate-400">No services match your selection.</p>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        <div>
            <x-card>
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-sm font-semibold text-slate-900">Order details</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">
                        <x-icon name="wallet" class="h-3.5 w-3.5" />
                        {{ \App\Support\Money::format($balance) }}
                    </span>
                </div>

                @if ($selected)
                    <form wire:submit="placeOrder" class="mt-4 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <x-brand-icon :name="$selected['platform'] === 'other' ? 'hashtag' : \App\Services\Providers\ProviderCatalog::platformIcon($selected['platform'])" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $selected['name'] }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ \App\Services\Providers\ProviderCatalog::platformLabel($selected['platform']) }}
                                    &middot; {{ $selected['category'] }}
                                    &middot; {{ $selected['min'] }}–{{ $selected['max'] }} units
                                </p>
                            </div>
                        </div>

                        @if ($selected['description'])
                            <div class="rounded-xl bg-accent-50 px-4 py-3 ring-1 ring-inset ring-accent-100">
                                <p class="text-sm text-accent-900">{{ $selected['description'] }}</p>
                                @if ($selected['link'])
                                    <p class="mt-2 flex items-start gap-1.5 text-xs font-medium text-accent-800">
                                        <x-icon name="link" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        {{ $selected['link'] }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div>
                            <label for="target" class="block text-sm font-medium text-slate-700">
                                Link ({{ \App\Services\Providers\ProviderCatalog::platformLabel($selected['platform']) }})
                            </label>
                            <input wire:model.live="target" id="target" type="url"
                                   placeholder="https://{{ $selected['platform'] }}.com/@your-username/…"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500
                                       {{ $linkError && ($this->target !== '' || $this->submitted) ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                            @if ($linkError && ($this->target !== '' || $this->submitted))
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $linkError }}</p>
                            @elseif ($errors->has('target'))
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('target') }}</p>
                            @endif
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-medium text-slate-700">Quantity</label>
                            <input wire:model.live="quantity" id="quantity" type="number" min="{{ $selected['min'] }}" max="{{ $selected['max'] }}"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500
                                       {{ $qtyError ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                            @if ($qtyError)
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $qtyError }}</p>
                            @elseif ($errors->has('quantity'))
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('quantity') }}</p>
                            @endif
                        </div>

                        <div class="space-y-2.5 rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                            @if ($avgTime)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <x-icon name="clock" class="h-3.5 w-3.5" /> Average start time
                                    </span>
                                    <span class="text-xs font-semibold text-slate-700">{{ $avgTime }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs text-slate-500">Price per unit</span>
                                <span class="text-xs font-semibold text-slate-700">{{ \App\Support\Money::format($unitPrice) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2.5">
                                <span class="text-sm font-medium text-slate-700">Total</span>
                                <span class="inline-flex items-center gap-1 text-xl font-bold tracking-tight text-brand-700">
                                    <x-icon name="bolt" class="h-4 w-4" />
                                    {{ \App\Support\Money::format($charge) }}
                                </span>
                            </div>
                        </div>

                        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="placeOrder">
                            <span wire:loading.remove wire:target="placeOrder">Place order</span>
                            <span wire:loading wire:target="placeOrder">Placing...</span>
                        </x-button>

                        <p class="flex items-start justify-center gap-1.5 text-center text-xs text-slate-400">
                            <x-icon name="shield" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            Confirm with your transaction PIN — deducted instantly from your wallet, and if the provider fails you're not charged
                        </p>
                    </form>
                @else
                    <div class="mt-6 flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 py-10 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-500">
                            <x-icon name="bolt" class="h-6 w-6" />
                        </span>
                        <p class="max-w-[16rem] text-sm text-slate-500">Choose a platform and a service above to see pricing and place your order.</p>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    @include('livewire.partials.transaction-pin')
</div>
