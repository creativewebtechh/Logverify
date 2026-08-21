<div>
    <x-auth-logo />

    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Reset your password</h2>
    <p class="mt-1 text-sm text-slate-500">Choose a new password for your account</p>

    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                required
                class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
            >
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="new-password"
                required
                class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                placeholder="At least 8 characters"
            >
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
            <input
                wire:model="password_confirmation"
                id="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                placeholder="Repeat your password"
            >
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">Reset password</span>
            <span wire:loading wire:target="resetPassword" class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Resetting...
            </span>
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Back to sign in</a>
    </p>
</div>
