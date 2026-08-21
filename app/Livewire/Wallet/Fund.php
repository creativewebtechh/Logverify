<?php

namespace App\Livewire\Wallet;

use App\Models\Transaction;
use App\Payments\Data\PaymentNotification;
use App\Payments\Exceptions\PaymentException;
use App\Payments\GatewayRegistry;
use App\Payments\PaymentMethod;
use App\Services\PaymentService;
use App\Services\PaymentSettings;
use App\Services\TransactionService;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class Fund extends Component
{
    public ?string $amount = null;

    public string $gateway = 'paystack';

    public string $paymentMethod = PaymentMethod::Card->value;

    public ?string $reference = null;

    public ?string $redirectUrl = null;

    public ?string $accessCode = null;

    public bool $sandbox = false;

    public bool $intentCreated = false;

    /** @var array<string, mixed>|null */
    public ?array $receipt = null;

    public function mount(): void
    {
        $this->gateway = PaymentSettings::defaultGateway();
        $this->paymentMethod = $this->methodsFor($this->gateway)[0]->value;
    }

    public function updatedGateway(): void
    {
        $this->resetIntent();
        $this->paymentMethod = $this->methodsFor($this->gateway)[0]->value;
        $this->resetValidation();
    }

    public function selectMethod(string $method): void
    {
        if ($this->intentCreated) {
            return;
        }

        $this->paymentMethod = $method;
        $this->resetValidation('paymentMethod');
    }

    /** @return PaymentMethod[] */
    public function methodsFor(string $gateway): array
    {
        return app(GatewayRegistry::class)->gateway($gateway)->supportedMethods();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum deposit is '.Money::format(config('app.min_deposit', 100)).'.',
        ];
    }

    public function initialize(PaymentService $payments, TransactionService $transactions, WalletService $wallets): void
    {
        $this->validate();

        $method = PaymentMethod::tryFrom($this->paymentMethod);

        if ($method === null || ! $payments->gateway($this->gateway)->supportsMethod($method)) {
            $this->addError('paymentMethod', 'The selected gateway does not support this payment method.');

            return;
        }

        $gateway = $payments->gateway($this->gateway);

        if (! $gateway->isConfigured() && ! $gateway->isTestMode()) {
            $this->addError('paymentMethod', 'This payment gateway is not available right now.');

            return;
        }

        $reference = $wallets->reference('LV');

        $transactions->capture(
            auth()->user(),
            (float) $this->amount,
            $reference,
            $this->gateway,
            $method,
            [
                'ip_address' => request()->ip(),
                'device' => substr((string) request()->userAgent(), 0, 255),
            ]
        );

        try {
            $intent = $payments->initializeDeposit(
                auth()->user(),
                (float) $this->amount,
                $reference,
                route('wallet'),
                $this->gateway,
                $method,
            );
        } catch (PaymentException $e) {
            Log::channel('payments')->warning('Payment initialization failed.', [
                'gateway' => $this->gateway,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $alternative = $this->gateway === 'paystack' ? 'Monnify' : 'Paystack';

            session()->flash('error', $e->getMessage().' You can try the '.$alternative.' gateway instead.');

            return;
        }

        $this->reference = $reference;
        $this->redirectUrl = $intent->redirectUrl;
        $this->accessCode = $intent->accessCode;
        $this->sandbox = $intent->sandbox;
        $this->intentCreated = true;

        if (! $intent->isRedirectable()) {
            session()->flash('status', 'The payment gateway is not configured. In sandbox mode the deposit is recorded as pending and no money is moved.');
        }
    }

    public function checkStatus(PaymentService $payments, TransactionService $transactions): void
    {
        if ($this->reference === null || $this->receipt !== null || $this->sandbox) {
            return;
        }

        $status = $transactions->statusFor($this->reference, $this->gateway);

        if ($status === Transaction::STATUS_SUCCESS) {
            $this->showReceipt();

            return;
        }

        if ($status === Transaction::STATUS_FAILED) {
            $this->handleFailure($transactions->findFor($this->reference, $this->gateway));

            return;
        }

        try {
            $notification = $payments->verify($this->reference, $this->gateway);
            $result = $transactions->fulfil($this->gateway, $notification);

            if ($result === 'success' || $result === 'already_processed') {
                $this->showReceipt();

                return;
            }

            if ($result === 'failed' || $result === 'amount_mismatch' || $result === 'currency_mismatch') {
                $this->handleFailure($notification);

                return;
            }
        } catch (Throwable) {
            // Gateway unreachable — fall through to the pending message.
        }

        session()->flash('status', 'Payment still pending. Check back shortly.');
    }

    public function fundAgain(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.wallet.fund', [
            'methods' => $this->methodsFor($this->gateway),
            'gatewayLabel' => app(GatewayRegistry::class)->gateway($this->gateway)->label(),
        ]);
    }

    private function handleFailure(?object $source): void
    {
        $cancelled = match (true) {
            $source instanceof PaymentNotification => $source->cancelled(),
            $source instanceof Transaction => ($source->meta['failure_reason'] ?? null) === PaymentNotification::REASON_CANCELLED,
            default => false,
        };

        session()->flash(
            'error',
            $cancelled
                ? 'Your payment was cancelled. No money has been moved.'
                : 'This payment was not completed. Please try again.'
        );

        $this->resetForm();
    }

    private function showReceipt(): void
    {
        $transaction = Transaction::query()
            ->where('reference', $this->reference)
            ->where('gateway', $this->gateway)
            ->first();

        if ($transaction !== null) {
            $this->receipt = [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'gateway_reference' => $transaction->gateway_reference,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency ?: 'NGN',
                'fee' => $transaction->fee !== null ? (float) $transaction->fee : null,
                'method' => PaymentMethod::tryFrom((string) $transaction->payment_method)?->label()
                    ?? ucfirst((string) $transaction->payment_method),
                'gateway' => $transaction->gateway,
                'date' => $transaction->paid_at?->toDayDateTimeString()
                    ?? $transaction->created_at?->toDayDateTimeString(),
            ];
        }

        session()->flash('success', 'Your wallet has been funded successfully.');
    }

    private function resetIntent(): void
    {
        $this->reset(['reference', 'redirectUrl', 'accessCode', 'sandbox', 'intentCreated', 'receipt']);
    }

    private function resetForm(): void
    {
        $this->reset(['amount', 'reference', 'redirectUrl', 'accessCode', 'sandbox', 'intentCreated', 'receipt']);
        $this->gateway = PaymentSettings::defaultGateway();
        $this->paymentMethod = $this->methodsFor($this->gateway)[0]->value;
        $this->resetValidation();
    }
}
