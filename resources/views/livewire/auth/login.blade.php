<div>
    @if (session('status'))
        <div class="mb-5 flex items-center gap-2.5 rounded-xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
            <x-icon name="check-badge" class="h-5 w-5 shrink-0" />
            {{ session('status') }}
        </div>
    @endif

    <x-auth-logo />

    <div class="text-center">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Welcome back</h2>
        <p class="mt-1.5 text-sm text-slate-500">Sign in to your {{ \App\Services\BrandingService::siteName() }} account</p>
    </div>

    <form wire:submit="login" class="mt-7 space-y-5">
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

        <div x-data="{ show: false }">
            <div class="relative">
                <input
                    wire:model="password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    autocomplete="current-password"
                    required
                    placeholder=" "
                    class="peer block w-full rounded-xl border-0 bg-slate-50/80 px-11 pb-2 pr-12 pt-6 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder-transparent transition duration-150 focus:bg-white focus:ring-2 focus:ring-brand-500"
                >
                <x-icon name="lock" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors peer-focus:text-brand-600" />
                <label for="password" class="pointer-events-none absolute left-11 top-2 text-xs font-medium text-slate-500 transition-all duration-150 peer-focus:font-medium peer-focus:text-brand-600 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:font-normal peer-placeholder-shown:text-slate-400">
                    Password
                </label>
                <button type="button"
                        @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 transition hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        :aria-label="show ? 'Hide password' : 'Show password'"
                        aria-pressed="false">
                    <x-icon name="eye" x-show="!show" class="h-5 w-5" />
                    <x-icon name="eye-off" x-show="show" x-cloak class="h-5 w-5" />
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 transition hover:text-brand-700">Forgot password?</a>
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login" class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Signing in...
            </span>
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 transition hover:text-brand-700">Create one</a>
    </p>
</div>
