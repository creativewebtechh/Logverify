<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Number Services</h1>
            <p class="text-sm text-slate-500">Catalog items, country/service matrix, pricing and visibility</p>
        </div>
        <x-button type="button" variant="secondary" size="sm" wire:click="syncAll" wire:loading.attr="disabled" wire:target="syncAll">Sync all providers</x-button>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
    @endif
    @if (isset($messages['all']))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">{{ $messages['all'] }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach ($providers as $provider)
            <div class="rounded-2xl border border-slate-100 bg-white px-4 py-3">
                <div class="flex items-center gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $provider->name }}</p>
                        <p class="text-xs text-slate-500">
                            @if ($provider->total_services !== null)
                                {{ number_format((int) $provider->total_services) }} services
                            @endif
                            @if ($provider->last_synced_at)
                                <span class="ml-1">· synced {{ $provider->last_synced_at->diffForHumans() }}</span>
                            @endif
                        </p>
                    </div>
                    <x-button type="button" variant="outline" size="xs" wire:click="syncProvider({{ $provider->id }})" wire:loading.attr="disabled" wire:target="syncProvider({{ $provider->id }})">Sync</x-button>
                </div>
                @if (isset($messages[$provider->id]))
                    <p class="mt-1.5 text-xs font-medium text-brand-600">{{ $messages[$provider->id] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <x-card>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="font-semibold text-slate-700">Catalog</p>
                        <p class="text-xs text-slate-500">Default markup {{ $defaultMarkup }}% — applied when a service has no per-item markup</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or country..."
                               class="w-52 rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <select wire:model.live="country" class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="">All countries</option>
                            @foreach ($countries as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="filterCategory" class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="">All categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="filterStatus" class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select wire:model.live="provider_id" class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="">All providers</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-max text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-2.5 pr-2 font-semibold"></th>
                                <th class="py-2.5 pr-4 font-semibold">Service</th>
                                <th class="py-2.5 pr-4 font-semibold">Country</th>
                                <th class="py-2.5 pr-4 font-semibold">Cost</th>
                                <th class="py-2.5 pr-4 font-semibold">Price</th>
                                <th class="py-2.5 pr-4 font-semibold">Margin</th>
                                <th class="py-2.5 pr-4 font-semibold">ETA</th>
                                <th class="py-2.5 pr-4 font-semibold">Favs</th>
                                <th class="py-2.5 pr-4 font-semibold">Orders</th>
                                <th class="py-2.5 pr-2 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($services as $service)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="py-3 pr-2">
                                        <input type="checkbox" wire:model.live="selected" value="{{ $service->id }}" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    </td>
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-slate-900">{{ $service->name }}</p>
                                        <p class="text-xs text-slate-500">{{ ucfirst($service->category) }} · {{ $service->provider?->name ?? 'Manual' }}</p>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center gap-1.5 text-slate-700">
                                            <span>{{ $service->flag() }}</span>
                                            {{ $service->country_name }}
                                        </span>
                                        <p class="text-xs text-slate-400 font-mono">{{ $service->country_code }}</p>
                                    </td>
                                    <td class="py-3 pr-4 font-mono text-slate-600">{{ $service->baseCost() !== null ? \App\Support\Money::format($service->baseCost(), 4) : '—' }}</td>
                                    <td class="py-3 pr-4 font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($service->price, 4) }}</td>
                                    <td class="py-3 pr-4">
                                        @if ($service->marginPercent() !== null)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ number_format($service->marginPercent(), 1) }}%</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $service->eta ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $service->favorites_count }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $service->orders_count }}</td>
                                    <td class="py-3 pr-2">
                                        <div class="flex flex-wrap items-center gap-1">
                                            @if ($service->featured)
                                                <x-badge tone="brand">Featured</x-badge>
                                            @endif
                                            @if ($service->popular)
                                                <x-badge tone="sky">Popular</x-badge>
                                            @endif
                                            @if ($service->hidden)
                                                <x-badge tone="slate">Hidden</x-badge>
                                            @endif
                                            <x-badge :tone="$service->status === 'active' ? 'brand' : 'amber'">{{ ucfirst($service->status) }}</x-badge>
                                        </div>
                                    </td>
                                    <td class="py-3 pr-2">
                                        <div class="flex items-center gap-1">
                                            <x-button type="button" variant="ghost" size="xs" wire:click="toggleFeatured({{ $service->id }})" wire:loading.attr="disabled" title="Toggle featured">{{ $service->featured ? 'Unfeature' : 'Feature' }}</x-button>
                                            <x-button type="button" variant="ghost" size="xs" wire:click="toggleHidden({{ $service->id }})" wire:loading.attr="disabled" title="Toggle hidden">{{ $service->hidden ? 'Show' : 'Hide' }}</x-button>
                                            <x-button type="button" variant="secondary" size="xs" wire:click="edit({{ $service->id }})">Edit</x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-10 text-center text-sm text-slate-500">No number services match your filters. Sync a provider catalog to import items.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $services->links() }}
                </div>
            </x-card>

            <x-card class="mt-6">
                <h2 class="text-sm font-bold text-slate-900">Bulk pricing</h2>
                <p class="mt-1 text-xs text-slate-500">Select services in the table, then apply markup and profit floors/caps. Only services with a provider cost are re-priced.</p>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Markup %</label>
                        <input type="number" step="0.01" min="0" wire:model="bulk_markup_percent" placeholder="e.g. 30"
                               class="mt-1 block w-32 rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Min profit</label>
                        <input type="number" step="0.01" wire:model="bulk_min_profit" placeholder="e.g. 50"
                               class="mt-1 block w-32 rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Max profit</label>
                        <input type="number" step="0.01" wire:model="bulk_max_profit" placeholder="e.g. 500"
                               class="mt-1 block w-32 rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                    @error('selected')
                        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center gap-2">
                        <x-button type="button" variant="primary" size="sm" wire:click="applyBulk" wire:loading.attr="disabled">Apply to {{ count($selected) }} selected</x-button>
                        <x-button type="button" variant="outline" size="sm" wire:click="applyDefaultMarkup" wire:loading.attr="disabled">Apply default markup ({{ $defaultMarkup }}%)</x-button>
                    </div>
                </div>
            </x-card>
        </div>

        <x-card class="lg:col-span-1">
            <h2 class="text-sm font-bold text-slate-900">Price history</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($history as $change)
                    <li class="py-3">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $change->numberService?->displayName() ?? 'Service #'.$change->number_service_id }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            <span class="font-mono">{{ \App\Support\Money::format($change->old_price, 4) }}</span>
                            <span class="text-slate-400">→</span>
                            <span class="font-mono font-semibold text-slate-700">{{ \App\Support\Money::format($change->new_price, 4) }}</span>
                        </p>
                        <p class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium">{{ $change->reason }}</span>
                            <span>{{ $change->created_at?->diffForHumans() }}</span>
                            @if ($change->old_price !== null && $change->reason !== 'rollback')
                                <button type="button" wire:click="rollback({{ $change->id }})" class="font-medium text-brand-600 hover:text-brand-700">Rollback</button>
                            @endif
                        </p>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No price changes yet.</li>
                @endforelse
            </ul>
        </x-card>
    </div>

    {{-- Edit modal --}}
    <div
        x-data="{ open: @entangle('showForm') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
        x-transition.opacity
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="open = false"></div>

        <div class="relative z-10 max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:rounded-2xl" @click.outside="open = false">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-slate-900">Edit number service</h3>
                    <p class="text-sm text-slate-500">Changes recalculate the customer price automatically.</p>
                </div>
                <button type="button" @click="open = false" class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="ns_name" class="block text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" id="ns_name" type="text" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_country" class="block text-sm font-medium text-slate-700">Country</label>
                    <input wire:model="country_name" id="ns_country" type="text" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('country_name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_category" class="block text-sm font-medium text-slate-700">Category</label>
                    <input wire:model="category" id="ns_category" type="text" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('category')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_eta" class="block text-sm font-medium text-slate-700">ETA label</label>
                    <input wire:model="eta" id="ns_eta" type="text" placeholder="e.g. 0:30" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </div>

                <div>
                    <label for="ns_eta_seconds" class="block text-sm font-medium text-slate-700">Reservation seconds</label>
                    <input wire:model="eta_seconds" id="ns_eta_seconds" type="number" min="60" max="14400" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <p class="mt-1 text-xs text-slate-400">How long the number is held before auto-refund (60–14400).</p>
                    @error('eta_seconds')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_markup" class="block text-sm font-medium text-slate-700">Markup %</label>
                    <input wire:model="markup_percent" id="ns_markup" type="number" step="0.01" min="0" placeholder="Leave blank for default" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('markup_percent')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_stock" class="block text-sm font-medium text-slate-700">Stock</label>
                    <input wire:model="stock" id="ns_stock" type="number" min="0" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('stock')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_min_profit" class="block text-sm font-medium text-slate-700">Min profit</label>
                    <input wire:model="min_profit" id="ns_min_profit" type="number" step="0.01" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('min_profit')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ns_max_profit" class="block text-sm font-medium text-slate-700">Max profit</label>
                    <input wire:model="max_profit" id="ns_max_profit" type="number" step="0.01" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('max_profit')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="ns_status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select wire:model="status" id="ns_status" class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-4 sm:col-span-2">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" wire:model="featured" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Featured
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" wire:model="popular" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Popular
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" wire:model="hidden" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Hidden from store
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 sm:col-span-2">
                    <x-button type="button" variant="ghost" wire:click="cancel">Cancel</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save changes</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
