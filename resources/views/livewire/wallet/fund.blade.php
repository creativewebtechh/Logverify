<div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('status'))
        <div class="rounded-2xl border border-brand-600/15 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($this->receipt)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Payment received</h2>
                            <p class="text-sm text-slate-500">Your wallet has been funded successfully.</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Confirmed</span>
                    </div>

                        <dl class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-slate-500">Amount</dt>
                                <dd class="text-lg font-bold text-brand-600">{{ \App\Support\Money::format($this->receipt['amount']) }}</dd>
                            </div>
                            @if ($this->receipt['fee'] !== null)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-sm text-slate-500">Gateway fee</dt>
                                    <dd class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($this->receipt['fee']) }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-slate-500">Method</dt>
                                <dd class="text-sm font-semibold text-slate-900">{{ $this->receipt['method'] }} · {{ ucfirst($this->receipt['gateway']) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-slate-500">Date</dt>
                                <dd class="text-sm font-semibold text-slate-900">{{ $this->receipt['date'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-slate-500">Reference</dt>
                                <dd class="font-mono text-xs text-slate-600">{{ $this->receipt['reference'] }}</dd>
                            </div>
                            @if (! empty($this->receipt['gateway_reference']))
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-sm text-slate-500">Gateway reference</dt>
                                    <dd class="font-mono text-xs text-slate-600">{{ $this->receipt['gateway_reference'] }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <x-button wire:click="fundAgain">
                                <x-icon name="plus" class="h-4 w-4" />
                                Fund again
                            </x-button>
                            <a href="{{ route('wallet.transactions.receipt', ['transaction' => $this->receipt['id']]) }}"
                               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-brand-300 hover:text-brand-700">
                                <x-icon name="download" class="h-4 w-4" />
                                Receipt
                            </a>
                        </div>
                </x-card>
            </div>

            <x-card>
                <p class="text-sm font-medium text-slate-500">Current balance</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                    {{ \App\Support\Money::format(auth()->user()->wallet?->balance ?? 0) }}
                </p>
            </x-card>

            <x-card>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon name="shield" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Secure payments</p>
                        <p class="text-xs text-slate-500">Payments processed by Paystack &amp; Monnify</p>
                    </div>
                </div>
            </x-card>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Fund your wallet</h2>
                            <p class="text-sm text-slate-500">Add funds instantly via {{ $gatewayLabel }}</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Instant</span>
                    </div>

                    <form wire:submit="initialize" class="mt-6 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Gateway</label>
                            <div class="mt-1.5 grid grid-cols-2 gap-3">
                                <button type="button" wire:click="$set('gateway', 'paystack')" @disabled($this->intentCreated)
                                        class="{{ $this->gateway === 'paystack' ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/30' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300' }} flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition disabled:opacity-50">
                                    <x-icon name="credit-card" class="h-4 w-4" />
                                    Paystack
                                </button>
                                <button type="button" wire:click="$set('gateway', 'monnify')" @disabled($this->intentCreated)
                                        class="{{ $this->gateway === 'monnify' ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/30' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300' }} flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition disabled:opacity-50">
                                    <x-icon name="banknotes" class="h-4 w-4" />
                                    Monnify
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-slate-700">Amount to deposit</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-lg font-semibold text-slate-400">{{ \App\Support\Money::symbol() }}</span>
                                <input
                                    wire:model.live="amount"
                                    id="amount"
                                    type="number"
                                    min="100"
                                    step="100"
                                    inputmode="numeric"
                                    required
                                    placeholder="0.00"
                                    class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-3 pl-11 text-2xl font-bold tracking-tight text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-300 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500"
                                >
                            </div>
                            @error('amount')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ([500, 1000, 5000, 10000] as $quick)
                                    <button type="button" wire:click="$set('amount', {{ $quick }})" @disabled($this->intentCreated)
                                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-brand-100 hover:text-brand-700 disabled:opacity-50">
                                        {{ \App\Support\Money::format($quick) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Payment method</label>
                            <div class="mt-1.5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($methods as $method)
                                    <button type="button" wire:click="selectMethod('{{ $method->value }}')" @disabled($this->intentCreated)
                                            class="{{ $this->paymentMethod === $method->value ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/30' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300' }} flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-semibold transition disabled:opacity-50">
                                        <x-icon name="{{ $this->paymentMethod === $method->value ? 'check' : 'bolt' }}" class="h-4 w-4" />
                                        {{ $method->label() }}
                                    </button>
                                @endforeach
                            </div>
                            @error('paymentMethod')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (! $this->intentCreated)
                            <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="initialize">
                                <span wire:loading.remove wire:target="initialize">Continue to payment</span>
                                <span wire:loading wire:target="initialize">Initializing...</span>
                            </x-button>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">Deposit pending</p>
                                <p class="mt-1 text-xs text-slate-500">Reference: {{ $this->reference }}</p>
                            </div>
                        @endif
                    </form>
                </x-card>

                @if ($this->intentCreated && $this->redirectUrl)
                    <div class="mt-6" wire:poll.5s="checkStatus">
                        <x-card>
                            <h3 class="text-sm font-semibold text-slate-900">Complete your payment</h3>
                            <p class="mt-1 text-sm text-slate-500">You'll be redirected to {{ $gatewayLabel }}'s secure checkout to complete the payment.</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ $this->redirectUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-accent-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 active:scale-[0.98]">
                                    <x-icon name="arrow-up-right" class="h-4 w-4" />
                                    Open {{ $gatewayLabel }} checkout
                                </a>
                                <x-button variant="secondary" wire:click="checkStatus" wire:loading.attr="disabled" wire:target="checkStatus">
                                    <x-icon name="check" class="h-4 w-4" />
                                    <span wire:loading.remove wire:target="checkStatus">I've paid</span>
                                    <span wire:loading wire:target="checkStatus">Checking...</span>
                                </x-button>
                            </div>
                            <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
                                <x-icon name="clock" class="h-3.5 w-3.5" />
                                Checking for payment confirmation automatically...
                            </p>
                        </x-card>
                    </div>
                @endif

                @if ($this->sandbox && $this->intentCreated)
                    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>Sandbox mode — payment gateway keys are not configured. Your deposit has been recorded as pending and no money will be moved.</p>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <x-card>
                    <p class="text-sm font-medium text-slate-500">Current balance</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                        {{ \App\Support\Money::format(auth()->user()->wallet?->balance ?? 0) }}
                    </p>
                </x-card>

                <x-card>
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <x-icon name="shield" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Secure payments</p>
                            <p class="text-xs text-slate-500">Payments processed by Paystack &amp; Monnify</p>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    @endif
</div>
