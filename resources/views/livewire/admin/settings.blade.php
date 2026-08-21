<div class="max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Settings</h1>
        <p class="text-sm text-slate-500">Platform-wide configuration</p>
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

    <div class="flex flex-wrap gap-1.5 border-b border-slate-200 pb-px" role="tablist" aria-label="Settings sections">
        @foreach ([
            'general' => 'General',
            'branding' => 'Branding',
            'payments' => 'Payments',
            'numbers' => 'SMS Numbers',
            'smtp' => 'SMTP',
            'whatsapp' => 'WhatsApp',
        ] as $key => $label)
            <button type="button"
                    role="tab"
                    wire:click="setTab('{{ $key }}')"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    class="rounded-t-xl px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600
                    {{ $tab === $key ? 'border border-b-0 border-slate-200 bg-white text-brand-700' : 'text-slate-500 hover:text-slate-800' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'general')
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">General</h2>
            <p class="mt-2 text-sm text-slate-500">
                Site-wide identity and referral defaults.
            </p>
            <form wire:submit="save" class="mt-5 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="site_name" class="block text-sm font-medium text-slate-700">Site name</label>
                        <input wire:model="site_name" id="site_name" type="text"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('site_name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-slate-700">Currency</label>
                        <input wire:model="currency" id="currency" type="text" maxlength="5"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('currency')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="referral_commission_percent" class="block text-sm font-medium text-slate-700">Referral commission (%)</label>
                        <input wire:model="referral_commission_percent" id="referral_commission_percent" type="number" min="0" max="50"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('referral_commission_percent')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </x-button>
            </form>
        </x-card>
    @endif

    @if ($tab === 'branding')
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">Branding</h2>
            <p class="mt-2 text-sm text-slate-500">
                Brand colors, logo and favicon. Brand colors apply instantly across the app, guest pages and admin panel.
            </p>
            <form wire:submit="saveBranding" class="mt-5 space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="brand_primary" class="block text-sm font-medium text-slate-700">Brand color</label>
                        <div class="mt-1.5 flex items-center gap-3">
                            <input wire:model="brand_primary" id="brand_primary" type="color" value="{{ $brand_primary }}"
                                   class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200 bg-white p-1">
                            <input wire:model="brand_primary" type="text" maxlength="7" placeholder="#123a93"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        </div>
                        @error('brand_primary')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="brand_accent" class="block text-sm font-medium text-slate-700">Accent color</label>
                        <div class="mt-1.5 flex items-center gap-3">
                            <input wire:model="brand_accent" id="brand_accent" type="color" value="{{ $brand_accent }}"
                                   class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200 bg-white p-1">
                            <input wire:model="brand_accent" type="text" maxlength="7" placeholder="#ea580c"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        </div>
                        @error('brand_accent')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-4 border-t border-slate-100 pt-5">
                    <div class="flex items-center gap-5">
                        <x-logo class="h-16 w-16" />
                        <div class="flex flex-col gap-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700">
                                {{ $custom_logo ? 'Custom logo' : 'Default logo' }}
                            </span>
                            <span>{{ $custom_logo ? 'Stored at '.$custom_logo : 'Fallback SVG is used if no image file exists.' }}</span>
                        </div>
                    </div>
                    <div>
                        <label for="logo" class="block text-sm font-medium text-slate-700">New logo</label>
                        <input wire:model="logo" id="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml"
                               class="mt-1.5 block w-full cursor-pointer rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('logo')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-button type="button" variant="secondary" wire:click="saveLogo" wire:loading.attr="disabled" wire:target="saveLogo">
                            <span wire:loading.remove wire:target="saveLogo">Upload logo</span>
                            <span wire:loading wire:target="saveLogo">Uploading...</span>
                        </x-button>
                        @if ($custom_logo)
                            <x-button type="button" variant="secondary" wire:click="removeLogo" wire:loading.attr="disabled" wire:target="removeLogo">
                                Reset logo
                            </x-button>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 border-t border-slate-100 pt-5">
                    <div class="flex items-center gap-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">
                            {{ $custom_favicon ? 'OK' : strtoupper(substr($site_name, 0, 1)) }}
                        </div>
                        <div class="flex flex-col gap-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700">
                                {{ $custom_favicon ? 'Custom favicon' : 'Default favicon' }}
                            </span>
                            <span>{{ $custom_favicon ? 'Stored at '.$custom_favicon : 'Used in the browser tab for every page.' }}</span>
                        </div>
                    </div>
                    <div>
                        <label for="favicon" class="block text-sm font-medium text-slate-700">New favicon</label>
                        <input wire:model="favicon" id="favicon" type="file" accept="image/png,image/x-icon,image/vnd.microsoft.icon"
                               class="mt-1.5 block w-full cursor-pointer rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('favicon')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveBranding">
                    <span wire:loading.remove wire:target="saveBranding">Save branding</span>
                    <span wire:loading wire:target="saveBranding">Saving...</span>
                </x-button>
            </form>
        </x-card>
    @endif

    @if ($tab === 'payments')
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">Funding gateway</h2>
            <p class="mt-2 text-sm text-slate-500">
                Configure Paystack and Monnify here. Keys are stored in the database and used on every
                deposit — no code or environment file changes required.
            </p>
            <form wire:submit="save" class="mt-5 space-y-6">
                <div>
                    <label for="payment_default_gateway" class="block text-sm font-medium text-slate-700">Default gateway</label>
                    <select wire:model="payment_default_gateway" id="payment_default_gateway"
                            class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <option value="paystack">Paystack</option>
                        <option value="monnify">Monnify</option>
                    </select>
                    @error('payment_default_gateway')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-4 border-t border-slate-100 pt-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Paystack</h3>
                            <p class="text-xs text-slate-500">Public and secret keys from your Paystack dashboard.</p>
                        </div>
                        <x-badge :tone="$paystack_test_mode ? 'amber' : 'brand'">
                            {{ $paystack_test_mode ? 'Sandbox' : 'Live' }}
                        </x-badge>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="paystack_public_key" class="block text-sm font-medium text-slate-700">Public key</label>
                            <input wire:model="paystack_public_key" id="paystack_public_key" type="password" autocomplete="off"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('paystack_public_key')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="paystack_secret_key" class="block text-sm font-medium text-slate-700">Secret key</label>
                            <input wire:model="paystack_secret_key" id="paystack_secret_key" type="password" autocomplete="off"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('paystack_secret_key')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input wire:model="paystack_test_mode" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Paystack test mode
                    </label>
                </div>

                <div class="space-y-4 border-t border-slate-100 pt-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Monnify</h3>
                            <p class="text-xs text-slate-500">Client key, secret and contract code from your Monnify dashboard.</p>
                        </div>
                        <x-badge :tone="$monnify_test_mode ? 'amber' : 'brand'">
                            {{ $monnify_test_mode ? 'Sandbox' : 'Live' }}
                        </x-badge>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="monnify_client_key" class="block text-sm font-medium text-slate-700">Client key</label>
                            <input wire:model="monnify_client_key" id="monnify_client_key" type="password" autocomplete="off"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('monnify_client_key')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="monnify_client_secret" class="block text-sm font-medium text-slate-700">Client secret</label>
                            <input wire:model="monnify_client_secret" id="monnify_client_secret" type="password" autocomplete="off"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('monnify_client_secret')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="monnify_contract_code" class="block text-sm font-medium text-slate-700">Contract code</label>
                            <input wire:model="monnify_contract_code" id="monnify_contract_code" type="text" autocomplete="off"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('monnify_contract_code')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="monnify_base_url" class="block text-sm font-medium text-slate-700">Base URL</label>
                            <input wire:model="monnify_base_url" id="monnify_base_url" type="url" autocomplete="off" placeholder="https://sandbox.monnify.com"
                                   class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            @error('monnify_base_url')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input wire:model="monnify_test_mode" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Monnify test mode
                    </label>
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save gateway</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </x-button>
            </form>
        </x-card>
    @endif

    @if ($tab === 'numbers')
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">SMS numbers</h2>
            <p class="mt-2 text-sm text-slate-500">
                Virtual number activation defaults used when a number service has no per-item values.
            </p>
            <form wire:submit="saveNumbers" class="mt-5 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="numbers_timeout_minutes" class="block text-sm font-medium text-slate-700">Activation timeout (minutes)</label>
                        <input wire:model="numbers_timeout_minutes" id="numbers_timeout_minutes" type="number" min="1" max="240"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">Auto-refund when no SMS arrives in this window.</p>
                        @error('numbers_timeout_minutes')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="numbers_duplicate_window_seconds" class="block text-sm font-medium text-slate-700">Duplicate window (seconds)</label>
                        <input wire:model="numbers_duplicate_window_seconds" id="numbers_duplicate_window_seconds" type="number" min="0" max="120"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">Blocks re-buying the same service right after a purchase.</p>
                        @error('numbers_duplicate_window_seconds')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="numbers_markup" class="block text-sm font-medium text-slate-700">Default markup (%)</label>
                        <input wire:model="numbers_markup" id="numbers_markup" type="number" min="0" max="1000"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">Applied to provider cost when a service has no markup set.</p>
                        @error('numbers_markup')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveNumbers">
                    <span wire:loading.remove wire:target="saveNumbers">Save numbers settings</span>
                    <span wire:loading wire:target="saveNumbers">Saving...</span>
                </x-button>
            </form>
        </x-card>
    @endif

    @if ($tab === 'smtp')
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">SMTP email</h2>
            <p class="mt-2 text-sm text-slate-500">
                Route verification and transaction emails through your own SMTP server.
                Leave disabled to keep using the application's default mailer.
            </p>
            <form wire:submit="saveSmtp" class="mt-5 space-y-4">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="smtp_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Enable custom SMTP
                </label>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="smtp_host" class="block text-sm font-medium text-slate-700">Host</label>
                        <input wire:model="smtp_host" id="smtp_host" type="text" placeholder="smtp.example.com"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('smtp_host')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="smtp_port" class="block text-sm font-medium text-slate-700">Port</label>
                        <input wire:model="smtp_port" id="smtp_port" type="number" min="1" max="65535"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('smtp_port')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="smtp_username" class="block text-sm font-medium text-slate-700">Username</label>
                        <input wire:model="smtp_username" id="smtp_username" type="text" autocomplete="off"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('smtp_username')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="smtp_password" class="block text-sm font-medium text-slate-700">Password</label>
                        <input wire:model="smtp_password" id="smtp_password" type="password" autocomplete="new-password"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">Leave blank to keep the existing password. Stored encrypted.</p>
                        @error('smtp_password')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="smtp_encryption" class="block text-sm font-medium text-slate-700">Encryption</label>
                        <select wire:model="smtp_encryption" id="smtp_encryption"
                                class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                        @error('smtp_encryption')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="smtp_from_address" class="block text-sm font-medium text-slate-700">From address</label>
                        <input wire:model="smtp_from_address" id="smtp_from_address" type="email"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('smtp_from_address')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="smtp_from_name" class="block text-sm font-medium text-slate-700">From name</label>
                        <input wire:model="smtp_from_name" id="smtp_from_name" type="text"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('smtp_from_name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveSmtp">
                    <span wire:loading.remove wire:target="saveSmtp">Save SMTP settings</span>
                    <span wire:loading wire:target="saveSmtp">Saving...</span>
                </x-button>
            </form>
        </x-card>

        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">Send test email</h2>
            <p class="mt-2 text-sm text-slate-500">
                Send a test message to verify the current mail configuration.
            </p>
            <form wire:submit="sendTestEmail" class="mt-5 space-y-4">
                <div>
                    <label for="smtp_test_email" class="block text-sm font-medium text-slate-700">Recipient email</label>
                    <input wire:model="smtp_test_email" id="smtp_test_email" type="email" placeholder="you@example.com"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                    @error('smtp_test_email')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="sendTestEmail">
                    <span wire:loading.remove wire:target="sendTestEmail">Send test email</span>
                    <span wire:loading wire:target="sendTestEmail">Sending...</span>
                </x-button>
            </form>
        </x-card>
    @endif

    @if ($tab === 'whatsapp')
        <x-card>
            <h2 class="text-sm font-semibold text-slate-900">WhatsApp widget</h2>
            <p class="mt-2 text-sm text-slate-500">
                Show a floating WhatsApp chat button on the app and landing pages.
            </p>
            <form wire:submit="saveWhatsApp" class="mt-5 space-y-4">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="whatsapp_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Enable WhatsApp widget
                </label>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="whatsapp_number" class="block text-sm font-medium text-slate-700">WhatsApp number</label>
                        <input wire:model="whatsapp_number" id="whatsapp_number" type="tel" placeholder="2348167263577"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">International format without spaces or a leading +.</p>
                        @error('whatsapp_number')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="whatsapp_label" class="block text-sm font-medium text-slate-700">Button label</label>
                        <input wire:model="whatsapp_label" id="whatsapp_label" type="text"
                               class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        @error('whatsapp_label')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="whatsapp_message" class="block text-sm font-medium text-slate-700">Default message</label>
                    <textarea wire:model="whatsapp_message" id="whatsapp_message" rows="3"
                              class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                    <p class="mt-1 text-xs text-slate-400">Pre-filled in the WhatsApp conversation.</p>
                    @error('whatsapp_message')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveWhatsApp">
                    <span wire:loading.remove wire:target="saveWhatsApp">Save WhatsApp settings</span>
                    <span wire:loading wire:target="saveWhatsApp">Saving...</span>
                </x-button>
            </form>
        </x-card>
    @endif
</div>
