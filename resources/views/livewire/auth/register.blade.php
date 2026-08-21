<div>
    <x-auth-logo />

    <div class="text-center">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Create your account</h2>
        <p class="mt-1.5 text-sm text-slate-500">Join {{ \App\Services\BrandingService::siteName() }} in under a minute</p>
    </div>

    <form wire:submit="register" class="mt-7 space-y-5">
        <div>
            <div class="relative">
                <input
                    wire:model="name"
                    id="name"
                    type="text"
                    autocomplete="name"
                    required
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pt-6 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="identification" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="name" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Full name
                </label>
            </div>
            @error('name')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="relative">
                <input
                    wire:model="username"
                    id="username"
                    type="text"
                    autocomplete="username"
                    maxlength="32"
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pt-6 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="at" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="username" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Username <span class="hidden text-slate-400 peer-placeholder-shown:inline">(optional)</span>
                </label>
            </div>
            @error('username')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="relative">
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    required
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pt-6 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="mail" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="email" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Email address
                </label>
            </div>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="passwordStrength">
            <div class="relative">
                <input
                    wire:model="password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    autocomplete="new-password"
                    required
                    placeholder=" "
                    @input="pw = $event.target.value"
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pr-12 pt-6 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="lock" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="password" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Password
                </label>
                <button type="button"
                        @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 transition hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        :aria-label="show ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" x-show="!show" class="h-5 w-5" />
                    <x-icon name="eye-off" x-show="show" x-cloak class="h-5 w-5" />
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror

            <template x-if="pw.length > 0">
                <div class="mt-3">
                    <div class="flex gap-1.5">
                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="score >= 1 ? strength.color : 'bg-slate-200'"></div>
                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="score >= 2 ? strength.color : 'bg-slate-200'"></div>
                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="score >= 3 ? strength.color : 'bg-slate-200'"></div>
                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="score >= 4 ? strength.color : 'bg-slate-200'"></div>
                    </div>
                    <p class="mt-1.5 text-xs font-medium" :class="strength.text" x-text="strength.label"></p>
                </div>
            </template>
        </div>

        <div x-data="{ show: false }">
            <div class="relative">
                <input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    :type="show ? 'text' : 'password'"
                    autocomplete="new-password"
                    required
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pr-12 pt-6 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="lock" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="password_confirmation" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Confirm password
                </label>
                <button type="button"
                        @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 transition hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        :aria-label="show ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" x-show="!show" class="h-5 w-5" />
                    <x-icon name="eye-off" x-show="show" x-cloak class="h-5 w-5" />
                </button>
            </div>
            @error('password_confirmation')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="{ show: false }">
            <div class="relative">
                <input
                    wire:model="pin"
                    id="pin"
                    :type="show ? 'text' : 'password'"
                    inputmode="numeric"
                    autocomplete="new-password"
                    pattern="\d{4}"
                    maxlength="4"
                    required
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pr-12 pt-6 text-sm tracking-[0.4em] text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="key" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="pin" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Transaction PIN
                </label>
                <button type="button"
                        @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 transition hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        :aria-label="show ? 'Hide PIN' : 'Show PIN'">
                    <x-icon name="eye" x-show="!show" class="h-5 w-5" />
                    <x-icon name="eye-off" x-show="show" x-cloak class="h-5 w-5" />
                </button>
            </div>
            <p class="mt-1.5 text-xs text-slate-400">A 4-digit PIN you'll enter to authorise every purchase.</p>
            @error('pin')
                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="{ show: false }">
            <div class="relative">
                <input
                    wire:model="pin_confirmation"
                    id="pin_confirmation"
                    :type="show ? 'text' : 'password'"
                    inputmode="numeric"
                    autocomplete="new-password"
                    pattern="\d{4}"
                    maxlength="4"
                    required
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pr-12 pt-6 text-sm tracking-[0.4em] text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="key" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="pin_confirmation" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Confirm transaction PIN
                </label>
                <button type="button"
                        @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 transition hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        :aria-label="show ? 'Hide PIN' : 'Show PIN'">
                    <x-icon name="eye" x-show="!show" class="h-5 w-5" />
                    <x-icon name="eye-off" x-show="show" x-cloak class="h-5 w-5" />
                </button>
            </div>
            @error('pin_confirmation')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="relative">
                <input
                    wire:model="referral_code"
                    id="referral_code"
                    type="text"
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pt-6 text-sm uppercase tracking-wider text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="gift" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="referral_code" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Referral code <span class="hidden text-slate-400 peer-placeholder-shown:inline">(optional)</span>
                </label>
            </div>
            @error('referral_code')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            <span>
                I agree to the
                <a href="#" class="font-semibold text-brand-600 transition hover:text-brand-700">Terms of Service</a>
                and
                <a href="#" class="font-semibold text-brand-600 transition hover:text-brand-700">Privacy Policy</a>
            </span>
        </label>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register" class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Creating...
            </span>
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition hover:text-brand-700">Sign in</a>
    </p>
</div>
