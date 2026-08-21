<div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Account security</h1>
        <p class="text-sm text-slate-500">Manage your transaction PIN — it's required to confirm every purchase</p>
    </div>

    <x-card>
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <x-icon name="shield" class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Transaction PIN</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    @if (auth()->user()->hasPin())
                        Your 4-digit PIN is set. You'll be asked for it to authorise every purchase. Change it any time.
                    @else
                        You haven't set a transaction PIN yet. Set one to unlock purchases — every paid order will ask for it.
                    @endif
                </p>
            </div>
        </div>

        <form wire:submit="updatePin" class="mt-6 max-w-sm space-y-4">
            @if (auth()->user()->hasPin())
                <div>
                    <label for="current_pin" class="block text-sm font-medium text-slate-700">Current PIN</label>
                    <input wire:model.live="current_pin" id="current_pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="current-password" placeholder="••••"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm tracking-[0.5em] ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('current_pin') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                    @if ($errors->has('current_pin'))
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('current_pin') }}</p>
                    @endif
                </div>
            @endif

            <div>
                <label for="pin" class="block text-sm font-medium text-slate-700">
                    {{ auth()->user()->hasPin() ? 'New PIN' : 'PIN' }}
                </label>
                <input wire:model.live="pin" id="pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" placeholder="••••"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm tracking-[0.5em] ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('pin') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('pin'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('pin') }}</p>
                @endif
            </div>

            <div>
                <label for="pin_confirmation" class="block text-sm font-medium text-slate-700">Confirm PIN</label>
                <input wire:model.live="pin_confirmation" id="pin_confirmation" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" placeholder="••••"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm tracking-[0.5em] ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('pin_confirmation') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('pin_confirmation'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('pin_confirmation') }}</p>
                @endif
            </div>

            <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="updatePin">
                <span wire:loading.remove wire:target="updatePin">{{ auth()->user()->hasPin() ? 'Update PIN' : 'Set PIN' }}</span>
                <span wire:loading wire:target="updatePin">Saving...</span>
            </x-button>
        </form>
    </x-card>

    <x-card>
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <x-icon name="lock" class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Password</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Keep your account secure by using a strong, unique password. Change it any time.
                </p>
            </div>
        </div>

        <form wire:submit="updatePassword" class="mt-6 max-w-sm space-y-4">
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700">Current password</label>
                <input wire:model.live="current_password" id="current_password" type="password" autocomplete="current-password" placeholder="••••••••"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('current_password') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('current_password'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('current_password') }}</p>
                @endif
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
                <input wire:model.live="password" id="password" type="password" autocomplete="new-password" placeholder="At least 8 characters"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('password') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('password'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
                <input wire:model.live="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat your new password"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('password_confirmation') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('password_confirmation'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('password_confirmation') }}</p>
                @endif
            </div>

            <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="updatePassword">
                <span wire:loading.remove wire:target="updatePassword">Update password</span>
                <span wire:loading wire:target="updatePassword">Saving...</span>
            </x-button>
        </form>
    </x-card>
</div>
