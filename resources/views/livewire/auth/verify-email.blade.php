<div>
    <x-auth-logo />

    @if (session('status'))
        <div class="mb-4 flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            <x-icon name="message" class="h-5 w-5 shrink-0" />
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            <x-icon name="shield" class="h-5 w-5 shrink-0" />
            {{ session('error') }}
        </div>
    @endif

    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Verify your email</h2>
    <p class="mt-1 text-sm text-slate-500">
        We sent a verification link to <span class="font-semibold text-slate-700">{{ auth()->user()->email }}</span>.
        You need to verify your email before you can access your dashboard.
    </p>

    <div class="mt-6 flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-700">
        <x-icon name="message" class="h-5 w-5 shrink-0" />
        Check your inbox (and spam folder) for the verification link.
    </div>

    <div class="mt-6 space-y-3">
        <x-button type="button" class="w-full" wire:click="resend" wire:loading.attr="disabled" wire:target="resend">
            <span wire:loading.remove wire:target="resend">Resend verification email</span>
            <span wire:loading wire:target="resend">Sending...</span>
        </x-button>

        <button type="button" wire:click="logout"
            class="block w-full text-center text-sm font-semibold text-brand-600 hover:text-brand-700">
            Sign out
        </button>
    </div>
</div>
