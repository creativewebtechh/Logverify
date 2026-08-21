<div>
    <x-auth-logo />

    @if ($sent)
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Check your email</h2>
        <p class="mt-1 text-sm text-slate-500">
            If an account exists for <span class="font-semibold text-slate-700">{{ $email }}</span>,
            we've emailed a password reset link. It expires in 60 minutes.
        </p>

        <div class="mt-6 flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-700">
            <x-icon name="message" class="h-5 w-5 shrink-0" />
            Check your inbox (and spam folder) for the reset link.
        </div>

        <div class="mt-6 space-y-3">
            <x-button type="button" variant="secondary" class="w-full" wire:click="sent = false">
                Resend link
            </x-button>
            <a href="{{ route('login') }}" class="block text-center text-sm font-semibold text-brand-600 hover:text-brand-700">
                Back to sign in
            </a>
        </div>
    @else
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Forgot your password?</h2>
        <p class="mt-1 text-sm text-slate-500">Enter your email and we'll send you a link to reset it</p>

        <form wire:submit="sendResetLink" class="mt-6 space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                    placeholder="you@example.com"
                >
                @error('email')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="sendResetLink">
                <span wire:loading.remove wire:target="sendResetLink">Send reset link</span>
                <span wire:loading wire:target="sendResetLink" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Sending...
                </span>
            </x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Remembered it?
            <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>
        </p>
    @endif
</div>
