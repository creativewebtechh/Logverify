@if ($showPinModal)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-sm sm:items-center" wire:click.self="closePinModal">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" wire:key="transaction-pin-modal">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ $pinModalTitle }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Enter your 4-digit transaction PIN to authorise this purchase.
                        @if ($pinModalAmount !== null)
                            You're paying <span class="font-semibold text-slate-900">{{ \App\Support\Money::format($pinModalAmount) }}</span>.
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="closePinModal" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100" aria-label="Close">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form wire:submit="confirmPurchase" class="mt-5 space-y-4">
                <div>
                    <label for="pin" class="block text-sm font-medium text-slate-700">Transaction PIN</label>
                    <input wire:model.live="pin" id="pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="current-password" placeholder="••••"
                           class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-center text-2xl font-bold tracking-[0.5em] ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('pin') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                    @if ($errors->has('pin'))
                        <p class="mt-1.5 text-center text-xs font-medium text-rose-600">{{ $errors->first('pin') }}</p>
                    @endif
                </div>

                <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="confirmPurchase">
                    <span wire:loading.remove wire:target="confirmPurchase">Confirm & pay</span>
                    <span wire:loading wire:target="confirmPurchase">Processing...</span>
                </x-button>
            </form>
        </div>
    </div>
@endif
