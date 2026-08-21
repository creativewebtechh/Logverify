<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Services</h1>
        <p class="text-sm text-slate-500">Manage social media boost services</p>
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

    <x-card>
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">{{ $editingId ? 'Edit service' : 'Add a service' }}</h2>
            @if ($showForm)
                <button type="button" wire:click="cancel" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Cancel</button>
            @endif
        </div>

        @if (! $showForm)
            <x-button type="button" variant="outline" size="sm" wire:click="$set('showForm', true)" class="mt-4">
                <x-icon name="plus" class="h-4 w-4" />
                New Service
            </x-button>
        @else
            <form wire:submit="{{ $editingId ? 'update' : 'add' }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <input wire:model="name" placeholder="Name" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="slug" placeholder="slug" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <textarea wire:model="description" placeholder="Description" rows="2" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:col-span-2"></textarea>
                <input wire:model="platform" placeholder="Platform (instagram, tiktok...)" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <select wire:model="type" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <option value="social">Social</option>
                    <option value="growth">Growth</option>
                </select>
                <input wire:model="price_per_unit" type="number" step="0.0001" min="0" placeholder="Price per unit" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="cost_per_unit" type="number" step="0.0001" min="0" placeholder="Cost per unit (provider rate)" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="min_qty" type="number" min="1" placeholder="Min qty" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="max_qty" type="number" min="1" placeholder="Max qty" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="avg_time" placeholder="Avg time (e.g. 5-40 minutes)" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="provider_service_id" placeholder="Provider service ID (e.g. 1234)" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <select wire:model="category_id" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <option value="">-- No category --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <input wire:model="image" placeholder="Image URL" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="tags" placeholder="Tags (comma separated)" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:col-span-2">
                <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-50 p-3 sm:col-span-2 sm:grid-cols-3">
                    @foreach ([
                        'featured' => 'Featured',
                        'recommended' => 'Recommended',
                        'best_seller' => 'Best seller',
                        'popular' => 'Popular',
                        'pinned' => 'Pinned',
                        'hidden' => 'Hidden',
                        'refill' => 'Refill',
                        'cancel' => 'Cancel',
                        'dripfeed' => 'Dripfeed',
                    ] as $flag => $label)
                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700">
                            <input type="checkbox" wire:model="{{ $flag }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <input wire:model="markup_percent" type="number" step="0.01" min="0" placeholder="Markup %" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="min_profit" type="number" step="0.0001" placeholder="Min profit" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <input wire:model="max_profit" type="number" step="0.0001" placeholder="Max profit" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <x-button type="submit" class="sm:col-span-2">
                    <x-icon name="plus" class="h-4 w-4" />
                    {{ $editingId ? 'Update service' : 'Add service' }}
                </x-button>
            </form>
            @if ($errors->any())
                <ul class="mt-3 space-y-1 text-xs font-medium text-rose-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </x-card>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Service</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Platform</th>
                        <th class="px-5 py-3">Unit price</th>
                        <th class="px-5 py-3">Range</th>
                        <th class="px-5 py-3">Avg time</th>
                        <th class="px-5 py-3">Margin</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($services as $service)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-900">{{ $service->name }}</p>
                                <p class="truncate text-xs text-slate-500 max-w-[240px]">{{ $service->description }}</p>
                                @if ($service->provider_service_id)
                                    <p class="mt-1 font-mono text-xs text-slate-400">{{ $service->provider_service_id }}</p>
                                @endif
                                @php
                                    $flags = [
                                        'featured' => $service->featured,
                                        'recommended' => $service->recommended,
                                        'best_seller' => $service->best_seller,
                                        'popular' => $service->popular,
                                        'pinned' => $service->pinned,
                                        'hidden' => $service->hidden,
                                        'refill' => $service->refill,
                                        'cancel' => $service->cancel,
                                        'dripfeed' => $service->dripfeed,
                                    ];
                                    $activeFlags = array_keys(array_filter($flags));
                                @endphp
                                @if ($activeFlags)
                                    <div class="mt-1.5 flex max-w-[240px] flex-wrap gap-1">
                                        @foreach ($activeFlags as $flag)
                                            <x-badge :tone="$flag === 'hidden' ? 'rose' : 'slate'">{{ ucwords(str_replace('_', ' ', $flag)) }}</x-badge>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($service->category)
                                    <x-badge tone="sky">{{ $service->category->name }}</x-badge>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $service->platform ? ucfirst($service->platform) : '—' }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ \App\Support\Money::format($service->price_per_unit, 4) }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $service->min_qty }}–{{ $service->max_qty }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $service->avg_time ?: '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                @if ($service->marginPercent() !== null)
                                    {{ $service->marginPercent() }}%
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <x-badge :tone="$service->status === 'active' ? 'brand' : 'slate'">{{ ucfirst($service->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <x-button type="button" variant="outline" size="xs" wire:click="edit({{ $service->id }})">Edit</x-button>
                                    <x-button type="button" variant="ghost" size="xs" wire:click="reprice({{ $service->id }})">Reprice</x-button>
                                    <button type="button" wire:click="toggleStatus({{ $service->id }})" class="text-xs font-semibold text-brand-600 hover:text-brand-700">{{ $service->status === 'active' ? 'Disable' : 'Enable' }}</button>
                                    <button type="button" wire:click="delete({{ $service->id }})" wire:confirm="Delete this service?" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-sm text-slate-500">No services yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($services->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $services->links() }}
            </div>
        @endif
    </x-card>
</div>
