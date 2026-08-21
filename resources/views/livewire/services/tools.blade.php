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

    <div class="space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Buy tools</h1>
                <p class="text-sm text-slate-500">Premium tools to grow and manage your accounts</p>
            </div>
            <select wire:model.live="category" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative sm:max-w-xs sm:flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search tools..." class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
            </div>
            <select wire:model.live="sort" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($tools as $tool)
            @php
                $outOfStock = (int) $tool->stock < 1;
            @endphp
            <x-card>
                @if ($tool->image)
                    <div class="relative -mx-5 -mt-5 h-36 overflow-hidden sm:-mx-6 sm:-mt-6">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($tool->image) }}" alt="{{ $tool->name }}" class="h-full w-full object-cover">
                        <div class="absolute left-3 top-3 flex flex-wrap items-center gap-1.5">
                            <x-product-badge :label="$tool->meta['badge'] ?? ($tool->featured ? 'Featured' : null)" />
                        </div>
                        <x-badge class="absolute right-3 top-3" tone="slate">{{ ucfirst($tool->category) }}</x-badge>
                    </div>
                @else
                    <div class="flex items-start justify-between">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <x-icon :name="$tool->icon ?: 'sparkles'" class="h-5 w-5" />
                        </span>
                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                            <x-product-badge :label="$tool->meta['badge'] ?? ($tool->featured ? 'Featured' : null)" />
                            <x-badge tone="slate">{{ ucfirst($tool->category) }}</x-badge>
                        </div>
                    </div>
                @endif

                <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $tool->name }}</h3>
                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $tool->description }}</p>

                <div class="mt-3 flex items-center gap-2">
                    @if ($outOfStock)
                        <x-badge tone="rose">Out of stock</x-badge>
                    @else
                        <x-badge tone="slate">{{ $tool->stock }} in stock</x-badge>
                    @endif
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                    <p class="text-lg font-bold text-brand-700">
                        {{ \App\Support\Money::format($tool->price) }}
                    </p>
                    <x-button size="sm" :disabled="$outOfStock" wire:click="buy({{ $tool->id }})" wire:loading.attr="disabled" wire:target="buy({{ $tool->id }})">
                        {{ $outOfStock ? 'Sold out' : 'Buy now' }}
                    </x-button>
                </div>
            </x-card>
        @empty
            <div class="col-span-full">
                <x-card>
                    <div class="py-10 text-center">
                        <x-icon name="sparkles" class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-3 text-sm font-medium text-slate-500">No tools available yet</p>
                    </div>
                </x-card>
            </div>
        @endforelse
    </div>

    @if ($tools->hasPages())
        <div>{{ $tools->links() }}</div>
    @endif

    @include('livewire.partials.transaction-pin')
</div>
