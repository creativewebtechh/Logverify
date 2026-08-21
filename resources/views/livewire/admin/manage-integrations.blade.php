<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">API Integrations</h1>
            <p class="text-sm text-slate-500">Connect one or more providers per channel. The preferred provider (lowest priority number) is used first and the router fails over to the next automatically.</p>
        </div>
        <x-button type="button" variant="secondary" size="sm" wire:click="syncAll" wire:loading.attr="disabled" wire:target="syncAll">Sync all</x-button>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    @if (($messages['all'] ?? null) !== null)
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ $messages['all'] }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            Please fix the errors below.
        </div>
    @endif

    <x-card>
        <p class="font-semibold text-slate-700">Driver guide</p>
        <ul class="mt-1.5 list-inside list-disc space-y-1 text-sm text-slate-500">
            <li><span class="font-medium text-slate-700">Generic (JSON)</span> — JSON POST to <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">{{ '{base_url}{order_endpoint}' }}</code> with <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">api_key</code> in the body and as <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">X-API-Key</code>.</li>
            <li><span class="font-medium text-slate-700">Grizzly SMS</span> — sms-activate compatible. GET <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">handler_api.php</code> and parses <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">ACCESS_NUMBER:id:number</code>.</li>
            <li><span class="font-medium text-slate-700">SMM panel v2</span> — form-encoded POST with <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">key</code>, <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">action=add</code>, <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">service</code>, <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">link</code>, <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">quantity</code>.</li>
        </ul>
        <p class="mt-2 text-sm text-slate-500">
            Each catalogue item carries a <span class="font-medium text-slate-700">Provider service ID</span> — set it on the number or service so the provider knows which product to deliver. API keys are stored encrypted and only ever shown masked.
        </p>
    </x-card>

    @foreach ([['numbers', 'Virtual numbers provider', 'Delivers real numbers when customers buy a number'], ['boost', 'Social boost / SMM provider', 'Places real orders when customers buy followers, likes or views']] as [$channel, $heading, $subtitle])
        @php
            $channelProviders = $providers->where('channel', $channel);
            $channelDrivers = $driverLabels[$channel];
        @endphp
        <x-card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ $heading }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-badge :tone="$channelProviders->isNotEmpty() ? 'brand' : 'slate'">
                        {{ $channelProviders->isNotEmpty() ? $channelProviders->count().' configured' : 'Not configured' }}
                    </x-badge>
                    <x-button variant="primary" size="sm" wire:click="newProvider('{{ $channel }}')">
                        Add provider
                    </x-button>
                </div>
            </div>

            @if ($channelProviders->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 p-6 text-center text-sm text-slate-500">
                    No {{ $channel }} provider yet. Add one to start routing {{ $channel }} orders.
                </div>
            @else
                <ul class="mt-5 divide-y divide-slate-100">
                    @foreach ($channelProviders as $provider)
                        <li class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $provider->name }}</p>
                                    <x-badge tone="sky">{{ $channelDrivers[$provider->driver] ?? $provider->driver }}</x-badge>
                                    @if ($provider->isConfigured())
                                        <x-badge tone="brand">Connected</x-badge>
                                    @else
                                        <x-badge tone="rose">Missing credentials</x-badge>
                                    @endif
                                    @if (! $provider->active)
                                        <x-badge tone="slate">Paused</x-badge>
                                    @endif
                                    <x-badge :tone="match ($provider->health_status) { 'healthy' => 'brand', 'degraded' => 'amber', 'unhealthy' => 'rose', default => 'slate' }">{{ ucfirst((string) $provider->health_status) }}</x-badge>
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1 font-mono">
                                        {{ $provider->masked_key ?? 'No API key set' }}
                                        @if ($provider->masked_key)
                                            @if ($revealed[$provider->id] ?? false)
                                                <span class="text-slate-700">{{ $provider->api_key }}</span>
                                                <button type="button" wire:click="$set('revealed.{{ $provider->id }}', false)" class="font-medium text-brand-600 hover:text-brand-700">hide</button>
                                            @else
                                                <button type="button" wire:click="reveal({{ $provider->id }})" class="font-medium text-brand-600 hover:text-brand-700">reveal</button>
                                            @endif
                                        @endif
                                    </span>
                                    @if ($provider->balance !== null)
                                        <span>Balance: <span class="font-semibold text-slate-700">{{ $provider->balance }}</span></span>
                                    @endif
                                    @if ($provider->last_synced_at)
                                        <span>Synced {{ $provider->last_synced_at->diffForHumans() }}</span>
                                    @endif
                                    @if ($provider->last_used_at)
                                        <span>Last used {{ $provider->last_used_at->diffForHumans() }}</span>
                                    @endif
                                    @php $stats = $logStats[$provider->id] ?? null; @endphp
                                    @if ($stats)
                                        <span>{{ $stats->total }} calls · {{ $stats->failures }} failed</span>
                                    @endif
                                    @if ($provider->success_rate !== null)
                                        <span>{{ number_format((float) $provider->success_rate, 2) }}% success</span>
                                    @endif
                                    @if ($provider->response_time_ms !== null)
                                        <span>{{ (int) $provider->response_time_ms }} ms</span>
                                    @endif
                                    @if ($provider->last_health_check_at)
                                        <span>Checked {{ $provider->last_health_check_at->diffForHumans() }}</span>
                                    @endif
                                    @if ($provider->last_error)
                                        <span class="text-rose-600">{{ $provider->last_error }}</span>
                                    @endif
                                </div>

                                @if (isset($messages[$provider->id]))
                                    @php
                                        $errorTone = collect(['Could not', 'error', 'HTTP', 'No provider', 'Add a', 'Save a', 'unsuccessful'])
                                            ->contains(fn ($needle) => str_contains($messages[$provider->id], $needle));
                                        $tone = $errorTone ? 'text-rose-600' : 'text-brand-600';
                                    @endphp
                                    <p class="mt-1.5 text-xs font-medium {{ $tone }}">{{ $messages[$provider->id] }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-button type="button" variant="outline" size="xs" wire:click="checkHealth({{ $provider->id }})" wire:loading.attr="disabled" wire:target="checkHealth({{ $provider->id }})">Health</x-button>
                                @if (in_array($channel, ['boost', 'numbers']))
                                    <x-button type="button" variant="outline" size="xs" wire:click="syncCatalog({{ $provider->id }})" wire:loading.attr="disabled" wire:target="syncCatalog({{ $provider->id }})">Sync catalog</x-button>
                                @endif
                                <x-button type="button" variant="outline" size="xs" wire:click="test({{ $provider->id }})" wire:loading.attr="disabled" wire:target="test({{ $provider->id }})">Test</x-button>
                                <x-button type="button" variant="outline" size="xs" wire:click="sync({{ $provider->id }})" wire:loading.attr="disabled" wire:target="sync({{ $provider->id }})">Sync</x-button>
                                <x-button type="button" variant="ghost" size="xs" wire:click="movePriority({{ $provider->id }}, 'up')" wire:loading.attr="disabled">↑</x-button>
                                <x-button type="button" variant="ghost" size="xs" wire:click="movePriority({{ $provider->id }}, 'down')" wire:loading.attr="disabled">↓</x-button>
                                <label class="ml-1 inline-flex cursor-pointer items-center gap-1.5 text-xs font-medium text-slate-600">
                                    <input type="checkbox" wire:click="toggleActive({{ $provider->id }})" {{ $provider->active ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    {{ $provider->active ? 'Active' : 'Paused' }}
                                </label>
                                <x-button type="button" variant="secondary" size="xs" wire:click="edit({{ $provider->id }})">Edit</x-button>
                                <x-button type="button" variant="danger" size="xs" wire:click="delete({{ $provider->id }})" wire:confirm="Remove this provider?">Delete</x-button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    @endforeach

    {{-- Add / edit provider modal --}}
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

        <div class="relative z-10 max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:rounded-2xl" @click.outside="open = false">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-slate-900">{{ $editingId ? 'Edit provider' : 'Add provider' }}</h3>
                    <p class="text-sm text-slate-500">{{ $channel === 'numbers' ? 'Virtual numbers channel' : 'Social boost channel' }}</p>
                </div>
                <button type="button" @click="open = false" class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="provider_name" class="block text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" id="provider_name" type="text" placeholder="Primary provider"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="provider_driver" class="block text-sm font-medium text-slate-700">Driver</label>
                    <select wire:model="driver" id="provider_driver"
                            class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @foreach ($driverLabels[$channel] ?? [] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('driver')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="provider_base_url" class="block text-sm font-medium text-slate-700">Base URL</label>
                    <input wire:model="base_url" id="provider_base_url" type="url" placeholder="https://api.provider.com"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('base_url')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="provider_api_key" class="block text-sm font-medium text-slate-700">API key</label>
                    <input wire:model="api_key" id="provider_api_key" type="password" autocomplete="off"
                           placeholder="{{ $editingId ? 'Leave blank to keep the current key' : 'Enter your API key' }}"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('api_key')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="provider_currency" class="block text-sm font-medium text-slate-700">Currency</label>
                    <input wire:model="currency" id="provider_currency" type="text" maxlength="5" placeholder="NGN"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <p class="mt-1.5 text-xs text-slate-400">ISO code used for balance display.</p>
                    @error('currency')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="provider_order_endpoint" class="block text-sm font-medium text-slate-700">Order endpoint</label>
                    <input wire:model="order_endpoint" id="provider_order_endpoint" type="text" placeholder="/v1/order"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm font-mono ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </div>
                <div>
                    <label for="provider_status_endpoint" class="block text-sm font-medium text-slate-700">Status endpoint</label>
                    <input wire:model="status_endpoint" id="provider_status_endpoint" type="text" placeholder="/v1/status"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm font-mono ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </div>
                <div>
                    <label for="provider_balance_endpoint" class="block text-sm font-medium text-slate-700">Balance endpoint</label>
                    <input wire:model="balance_endpoint" id="provider_balance_endpoint" type="text" placeholder="/v1/balance"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm font-mono ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </div>
                <div>
                    <label for="provider_services_endpoint" class="block text-sm font-medium text-slate-700">Services endpoint</label>
                    <input wire:model="services_endpoint" id="provider_services_endpoint" type="text" placeholder="/v1/services"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm font-mono ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                </div>

                <div>
                    <label for="provider_priority" class="block text-sm font-medium text-slate-700">Priority</label>
                    <input wire:model="priority" id="provider_priority" type="number" min="0"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <p class="mt-1.5 text-xs text-slate-400">Lower numbers are tried first.</p>
                    @error('priority')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end pb-1">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" wire:model="active" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Active (route orders to this provider)
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <label for="provider_notes" class="block text-sm font-medium text-slate-700">Notes</label>
                    <textarea wire:model="notes" id="provider_notes" rows="3" placeholder="Optional notes about this provider"
                              class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                    @error('notes')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-end gap-3 sm:col-span-2">
                    <x-button type="button" variant="ghost" wire:click="cancel">Cancel</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Add provider' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
