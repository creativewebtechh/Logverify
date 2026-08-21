<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Virtual numbers</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Add/Edit Number Listing</h1>
            <p class="mt-1 text-sm text-slate-500">Create and manage the numbers catalogue delivered by your provider</p>
        </div>
        <x-button variant="orange" wire:click="startAdd"
                  x-data x-on:click="$nextTick(() => document.getElementById('number-listing-form')?.scrollIntoView({ behavior: 'smooth' }))">
            <x-icon name="plus" class="h-4 w-4" />
            Add Number
        </x-button>
    </div>

    @if (session('success'))
        <div class="flex items-center gap-2.5 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            <x-icon name="check" class="h-4 w-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif

    {{-- Add / Edit form --}}
    <form wire:submit="add" id="number-listing-form" class="space-y-6">
        @if ($editingId)
            <div class="flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3">
                <p class="text-sm font-medium text-brand-700">You are editing an existing listing.</p>
                <button type="button" wire:click="startAdd" class="text-sm font-semibold text-brand-700 underline-offset-2 hover:underline">
                    Cancel editing
                </button>
            </div>
        @endif

        {{-- Card 1: Number details --}}
        <div class="rounded-2xl border border-slate-100 bg-white">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon name="phone" class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Number Details</h2>
                        <p class="text-xs text-slate-500">Core information buyers see on the listing</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 md:grid-cols-2">
                <div>
                    <label for="number-country" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Country <span class="text-rose-500">*</span>
                    </label>
                    <input id="number-country" type="text" wire:model="country"
                           placeholder="e.g. Nigeria, United States"
                           class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('country')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="number-category" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Type <span class="text-rose-500">*</span>
                    </label>
                    <select id="number-category" wire:model="category"
                            class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="sms">SMS</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="voice">Voice</option>
                    </select>
                    @error('category')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="number-status" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Status <span class="text-rose-500">*</span>
                    </label>
                    <select id="number-status" wire:model="status"
                            class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="number-masked" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Display number <span class="text-rose-500">*</span>
                    </label>
                    <input id="number-masked" type="text" wire:model="masked_number"
                           placeholder="+234 (•••) •••-0000"
                           class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <p class="mt-1.5 text-xs text-slate-500">Masked preview shown to buyers. The real number is delivered by the provider after purchase.</p>
                    @error('masked_number')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="number-price" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Price <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-slate-500">₦</span>
                        <input id="number-price" type="number" step="0.01" min="0" wire:model="price"
                               placeholder="0.00"
                               class="w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-9 pr-3.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                    @error('price')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="number-service-id" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Provider service ID
                    </label>
                    <input id="number-service-id" type="text" wire:model="provider_service_id"
                           placeholder="e.g. wa, sms, vo"
                           class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <p class="mt-1.5 text-xs text-slate-500">The product code your provider uses for this number type.</p>
                    @error('provider_service_id')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="number-provider" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Provider route
                    </label>
                    <select id="number-provider" wire:model="provider_id"
                            class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="">Auto — preferred + failover</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Optional: pin this listing to a specific provider.</p>
                    @error('provider_id')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Card 2: Delivery provider --}}
        <div class="rounded-2xl border border-slate-100 bg-white">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon name="shield" class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Delivery provider</h2>
                        <p class="text-xs text-slate-500">Numbers are fulfilled by a single provider, configured in API Integrations</p>
                    </div>
                </div>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                        <p class="text-xs font-medium text-slate-500">Driver</p>
                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">
                            {{ $provider_driver === 'grizzly' ? 'Grizzly SMS (sms-activate)' : 'Generic (JSON)' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                        <p class="text-xs font-medium text-slate-500">Balance</p>
                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">{{ $provider_balance ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                        <p class="text-xs font-medium text-slate-500">Last sync</p>
                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">
                            {{ $provider_last_sync ? \Illuminate\Support\Carbon::parse($provider_last_sync)->diffForHumans() : '—' }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Connection</p>
                            <div class="mt-1">
                                <x-badge :tone="$provider_connected ? 'brand' : 'slate'">
                                    {{ $provider_connected ? 'Connected' : 'Not configured' }}
                                </x-badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-2xl border border-brand-600/15 bg-brand-50 px-4 py-3.5">
                    <x-icon name="cog" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                    <p class="text-sm font-medium text-brand-900">
                        When a customer buys a number, this provider is called and the real number is delivered to their order.
                        Configure the API key and driver on the
                        <a href="{{ route('admin.integrations') }}" class="font-semibold text-brand-700 underline-offset-2 hover:underline">API Integrations</a>
                        page.
                    </p>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
            <x-button type="submit" size="lg" class="w-full justify-center">
                <x-icon name="check" class="h-5 w-5" />
                Save Number
            </x-button>
            @if ($errors->any())
                <ul class="mt-3 space-y-1 text-xs font-medium text-rose-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </form>

    {{-- Management table --}}
    <div class="rounded-2xl border border-slate-100 bg-white">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Number Listings</h2>
                <p class="text-xs text-slate-500">{{ $numbers->total() }} listing(s) in the catalogue</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="search" class="h-4 w-4" />
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search listings..."
                           class="w-full rounded-xl border-0 bg-slate-50 py-2 pl-9 pr-3.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:w-56">
                </div>
                <select wire:model.live="filterStatus"
                        class="w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:w-40">
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="sold">Sold</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Number</th>
                        <th class="px-5 py-3">Country</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Price</th>
                        <th class="px-5 py-3">Provider service ID</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($numbers as $number)
                        <tr class="transition hover:bg-slate-50/50">
                            <td class="px-5 py-3 font-mono font-semibold text-slate-900">{{ $number->masked_number }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $number->country }}</td>
                            <td class="px-5 py-3">
                                <x-badge tone="sky">{{ ucfirst($number->category) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ \App\Support\Money::format($number->price) }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $number->provider_service_id ?: '—' }}</td>
                            <td class="px-5 py-3">
                                <x-badge :tone="$number->status === 'available' ? 'brand' : 'slate'">{{ ucfirst($number->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="edit({{ $number->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete({{ $number->id }})" wire:confirm="Delete this number?"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <x-icon name="phone" class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 text-sm font-medium text-slate-500">
                                    {{ filled($search) || filled($filterStatus) ? 'No listings match your filters' : 'No numbers yet — add your first listing' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($numbers->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $numbers->links() }}
            </div>
        @endif
    </div>
</div>
