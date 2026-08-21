<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Accounts marketplace</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Add/Edit Account Listing</h1>
            <p class="mt-1 text-sm text-slate-500">Create and manage premium social media account listings</p>
        </div>
        <x-button variant="orange" wire:click="startAdd"
                  x-data x-on:click="$nextTick(() => document.getElementById('account-listing-form')?.scrollIntoView({ behavior: 'smooth' }))">
            <x-icon name="plus" class="h-4 w-4" />
            Add Account
        </x-button>
    </div>

    @if (session('success'))
        <div class="flex items-center gap-2.5 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            <x-icon name="check" class="h-4 w-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif

    {{-- Add / Edit form --}}
    <form wire:submit="add" id="account-listing-form" class="space-y-6">
        @if ($editingId)
            <div class="flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3">
                <p class="text-sm font-medium text-brand-700">You are editing an existing listing.</p>
                <button type="button" wire:click="startAdd" class="text-sm font-semibold text-brand-700 underline-offset-2 hover:underline">
                    Cancel editing
                </button>
            </div>
        @endif

        {{-- Card 1: Account Details --}}
        <div class="rounded-2xl border border-slate-100 bg-white">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon name="at" class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Account Details</h2>
                        <p class="text-xs text-slate-500">Core information buyers see on the listing</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 md:grid-cols-2">
                <div>
                    <label for="account-platform" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Platform <span class="text-rose-500">*</span>
                    </label>
                    <select id="account-platform" wire:model="platform"
                            class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="">Select a platform</option>
                        @foreach (['instagram', 'tiktok', 'whatsapp', 'telegram', 'facebook', 'twitter', 'gmail'] as $p)
                            <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                    @error('platform')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account-status" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Status <span class="text-rose-500">*</span>
                    </label>
                    <select id="account-status" wire:model="status"
                            class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                        <option value="pending">Pending</option>
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="account-title" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Account Title <span class="text-rose-500">*</span>
                    </label>
                    <input id="account-title" type="text" wire:model="title"
                           placeholder="e.g., Aged Instagram Account - 10K Followers"
                           class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('title')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="account-description" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Description
                    </label>
                    <textarea id="account-description" rows="4" wire:model="description"
                              placeholder="Describe stats, niche, age of account, engagement rate, and what's included..."
                              class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account-price" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Price <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-slate-500">₦</span>
                        <input id="account-price" type="number" step="0.01" min="0" wire:model="price"
                               placeholder="0.00"
                               class="w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-9 pr-3.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                    @error('price')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account-stock" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Stock (units)
                    </label>
                    <input id="account-stock" type="number" min="0" wire:model="stock"
                           placeholder="1"
                           class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    <p class="mt-1.5 text-xs text-slate-500">How many units are in inventory.</p>
                    @error('stock')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="account-image" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Demo image
                    </label>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="group flex cursor-pointer items-center gap-2.5 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-brand-400 hover:text-brand-600">
                            <x-icon name="upload" class="h-4 w-4" />
                            {{ $image ? $image->getClientOriginalName() : ($existingImage ? 'Replace image' : 'Upload image') }}
                            <input id="account-image" type="file" wire:model="image" accept="image/png,image/jpeg,image/webp" class="sr-only">
                        </label>
                        @if ($existingImage)
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="flex h-10 w-10 overflow-hidden rounded-lg ring-1 ring-slate-200">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImage) }}" alt="Current demo image" class="h-full w-full object-cover">
                                </span>
                                Current image
                            </div>
                        @endif
                        @if ($image)
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="flex h-10 w-10 overflow-hidden rounded-lg ring-1 ring-slate-200">
                                    <img src="{{ $image->temporaryUrl() }}" alt="New demo image" class="h-full w-full object-cover">
                                </span>
                                New image
                            </div>
                        @endif
                    </div>
                    @error('image')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="checkbox" wire:model="featured" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-semibold text-slate-700">Feature this listing</span>
                        <span class="text-xs text-slate-500">Featured listings are highlighted on the marketplace page.</span>
                    </label>
                </div>

                <div>
                    <label for="account-link" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Account Link
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <x-icon name="link" class="h-4 w-4" />
                        </span>
                        <input id="account-link" type="url" wire:model="account_link"
                               placeholder="https://instagram.com/username"
                               class="w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-10 pr-3.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Link buyers can preview before purchase.</p>
                    @error('account_link')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Card 2: Secure Account Credentials --}}
        <div class="rounded-2xl border border-slate-100 bg-white">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon name="key" class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Secure Account Credentials</h2>
                        <p class="text-xs text-slate-500">Login details delivered to the buyer after purchase</p>
                    </div>
                </div>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
                <div class="flex items-start gap-3 rounded-2xl border border-brand-600/15 bg-brand-50 px-4 py-3.5">
                    <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                    <p class="text-sm font-medium text-brand-900">
                        These credentials are hidden until they purchase the account.
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200">
                    <div class="flex items-center gap-2 text-slate-500">
                        <x-icon name="lock" class="h-4 w-4" />
                        <p class="text-xs font-semibold uppercase tracking-wide">Secure login details</p>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="credential-email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                            <input id="credential-email" type="email" wire:model="credentials.email" placeholder="account@email.com"
                                   class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('credentials.email')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="credential-password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                            <input id="credential-password" type="password" wire:model="credentials.password" placeholder="••••••••"
                                   class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('credentials.password')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="credential-phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Phone</label>
                            <input id="credential-phone" type="tel" wire:model="credentials.phone" placeholder="+2348012345678"
                                   class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('credentials.phone')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <p class="mt-4 flex items-center gap-1.5 text-xs text-slate-500">
                        <x-icon name="shield" class="h-3.5 w-3.5" />
                        Credentials are kept private and automatically unlocked on the customer's order after a successful purchase.
                    </p>
                </div>
            </div>
        </div>

        {{-- Card 3: Screenshots Upload --}}
        <div class="rounded-2xl border border-slate-100 bg-white">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon name="camera" class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Screenshots</h2>
                        <p class="text-xs text-slate-500">Upload screenshots of the account. The first screenshot will be used as the thumbnail.</p>
                    </div>
                </div>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
                @if (count($existingScreenshots) > 0)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        @foreach ($existingScreenshots as $index => $path)
                            <div class="relative overflow-hidden rounded-2xl ring-1 ring-slate-200">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="Screenshot {{ $index + 1 }}"
                                     class="h-32 w-full object-cover">
                                <span class="absolute left-2 top-2 rounded-full bg-slate-900/70 px-2.5 py-1 text-[11px] font-semibold text-white">
                                    Screenshot {{ $index + 1 }}
                                </span>
                                <button type="button" wire:click="removeScreenshot({{ $index }})"
                                        class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white text-rose-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-rose-50"
                                        title="Remove screenshot">
                                    <x-icon name="x" class="h-4 w-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @for ($i = 0; $i < 3; $i++)
                        @php
                            $label = $i === 0 ? 'Screenshot 1 (Thumbnail)' : 'Screenshot '.($i + 1).' (Optional)';
                        @endphp
                        <div>
                            <label for="screenshot-{{ $i }}"
                                   class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition hover:border-brand-400 hover:bg-brand-50/50">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-400 ring-1 ring-slate-200 transition group-hover:text-brand-600">
                                    <x-icon name="upload" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                                <span class="text-xs text-slate-500">Click to upload (PNG, JPG, WebP, Max 5MB)</span>
                                <input id="screenshot-{{ $i }}" type="file" wire:model="screenshots.{{ $i }}"
                                       accept="image/png,image/jpeg,image/webp" class="sr-only">
                            </label>
                            <div wire:loading wire:target="screenshots.{{ $i }}" class="mt-2 text-center text-xs font-medium text-brand-600">
                                Uploading...
                            </div>
                            @if (! empty($screenshots[$i]))
                                <div class="relative mt-3 overflow-hidden rounded-2xl ring-1 ring-slate-200">
                                    <img src="{{ $screenshots[$i]->temporaryUrl() }}" alt="New screenshot preview" class="h-32 w-full object-cover">
                                    <span class="absolute right-2 top-2 rounded-full bg-brand-600 px-2.5 py-1 text-[11px] font-semibold text-white">New</span>
                                </div>
                            @endif
                            @error("screenshots.$i")
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endfor
                </div>

                <div class="flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3.5">
                    <x-icon name="camera" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                    <p class="text-sm leading-relaxed text-brand-800">
                        The first screenshot becomes the listing thumbnail. Please include clear proof of followers
                        and engagement so buyers can verify the account before purchasing.
                    </p>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
            <x-button type="submit" size="lg" class="w-full justify-center">
                <x-icon name="check" class="h-5 w-5" />
                Save Account
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
                <h2 class="text-sm font-bold text-slate-900">Account Listings</h2>
                <p class="text-xs text-slate-500">{{ $accounts->total() }} listing(s) in the marketplace</p>
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
                    <option value="pending">Pending</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Account</th>
                        <th class="px-5 py-3">Platform</th>
                        <th class="px-5 py-3">Price</th>
                        <th class="px-5 py-3">Stock</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($accounts as $account)
                        @php
                            $thumb = $account->image ?? $account->meta['screenshots'][0] ?? null;
                            $thumbUrl = $thumb && \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb)
                                ? \Illuminate\Support\Facades\Storage::url($thumb)
                                : null;
                        @endphp
                        <tr class="transition hover:bg-slate-50/50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                        @if ($thumbUrl)
                                            <img src="{{ $thumbUrl }}" alt="{{ $account->title }}" class="h-full w-full object-cover">
                                        @else
                                            <x-icon name="at" class="h-5 w-5 text-slate-400" />
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900">{{ $account->title }}</p>
                                        <p class="max-w-[260px] truncate text-xs text-slate-500">{{ $account->description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-badge tone="slate">{{ ucfirst($account->platform) }}</x-badge>
                                    @if ($account->featured)
                                        <x-badge tone="amber">Featured</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ \App\Support\Money::format($account->price) }}</td>
                            <td class="px-5 py-3 text-xs font-medium text-slate-500">{{ $account->stock }}</td>
                            <td class="px-5 py-3">
                                <x-badge :tone="$account->status === 'available' ? 'brand' : ($account->status === 'pending' ? 'amber' : 'slate')">
                                    {{ ucfirst($account->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="edit({{ $account->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete({{ $account->id }})" wire:confirm="Delete this account?"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-icon name="at" class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 text-sm font-medium text-slate-500">
                                    {{ filled($search) || filled($filterStatus) ? 'No listings match your filters' : 'No accounts yet — add your first listing' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($accounts->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>
