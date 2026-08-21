<?php

namespace App\Services;

use App\Events\DepositSucceeded;
use App\Jobs\CreditReferralCommission;
use App\Models\Transaction;
use App\Models\User;
use App\Payments\Data\PaymentNotification;
use App\Payments\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Captures and settles wallet funding transactions.
 *
 * Fulfilment is idempotent: the transaction row is locked for update so a
 * duplicated webhook (or a concurrent verify + webhook race) can never
 * credit the wallet more than once.
 */
class TransactionService
{
    public function __construct(private WalletService $wallets) {}

    public function capture(
        User $user,
        float $amount,
        string $reference,
        string $gateway,
        PaymentMethod $method,
        array $context = [],
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'currency' => config('app.currency', 'NGN'),
            'balance_after' => $this->wallets->balance($user),
            'status' => Transaction::STATUS_PENDING,
            'payment_status' => Transaction::PAYMENT_PENDING,
            'reference' => $reference,
            'gateway' => $gateway,
            'payment_method' => $method->value,
            'description' => 'Wallet funding',
            'meta' => [
                'test_mode' => $this->testMode($gateway),
            ],
            'ip_address' => $context['ip_address'] ?? null,
            'device' => $context['device'] ?? null,
        ]);
    }

    /**
     * Settle a funding transaction from a normalised gateway notification.
     *
     * @return string one of: success, already_processed, not_found,
     *                pending, failed, amount_mismatch, currency_mismatch
     */
    public function fulfil(string $gateway, PaymentNotification $notification): string
    {
        return DB::transaction(function () use ($gateway, $notification) {
            $transaction = Transaction::query()
                ->where('reference', $notification->reference)
                ->where('gateway', $gateway)
                ->lockForUpdate()
                ->first();

            if ($transaction === null) {
                Log::channel('payments')->warning('Fulfil skipped: unknown reference.', [
                    'gateway' => $gateway,
                    'reference' => $notification->reference,
                ]);

                return 'not_found';
            }

            if ($transaction->status !== Transaction::STATUS_PENDING) {
                return 'already_processed';
            }

            if ($notification->failed()) {
                $transaction->update($this->failureAttributes($transaction, $notification, Transaction::PAYMENT_FAILED));

                Log::channel('payments')->info('Funding payment failed.', [
                    'gateway' => $gateway,
                    'reference' => $notification->reference,
                    'reason' => $notification->reason,
                ]);

                return 'failed';
            }

            if (! $notification->succeeded()) {
                return 'pending';
            }

            $currencyMismatch = filled($notification->currency)
                && strtoupper($notification->currency) !== strtoupper((string) config('app.currency', 'NGN'));

            if ($currencyMismatch) {
                $transaction->update($this->failureAttributes($transaction, $notification, Transaction::PAYMENT_CURRENCY_MISMATCH));

                Log::channel('payments')->warning('Funding rejected: currency mismatch.', [
                    'gateway' => $gateway,
                    'reference' => $notification->reference,
                    'currency' => $notification->currency,
                    'expected' => config('app.currency', 'NGN'),
                ]);

                return 'currency_mismatch';
            }

            if (abs($notification->amountPaid - (float) $transaction->amount) > 0.01) {
                $transaction->update($this->failureAttributes($transaction, $notification, Transaction::PAYMENT_AMOUNT_MISMATCH));

                Log::channel('payments')->warning('Funding rejected: amount mismatch.', [
                    'gateway' => $gateway,
                    'reference' => $notification->reference,
                    'expected' => (float) $transaction->amount,
                    'received' => $notification->amountPaid,
                ]);

                return 'amount_mismatch';
            }

            [$wallet, $wasFirstDeposit] = $this->wallets->creditSilently(
                $transaction->user,
                $notification->amountPaid
            );

            $transaction->update([
                'status' => Transaction::STATUS_SUCCESS,
                'payment_status' => Transaction::PAYMENT_SUCCESS,
                'amount' => $notification->amountPaid,
                'currency' => $notification->currency ?: $transaction->currency,
                'fee' => $notification->fee,
                'balance_after' => (float) $wallet->balance,
                'gateway_reference' => $notification->gatewayReference ?: $transaction->gateway_reference,
                'gateway_response' => $notification->raw,
                'webhook_payload' => $notification->raw,
                'payment_method' => $notification->method?->value ?? $transaction->payment_method,
                'paid_at' => now(),
            ]);

            $transaction->user->update(['is_verified' => true]);

            Log::channel('payments')->info('Wallet funded via gateway.', [
                'gateway' => $gateway,
                'reference' => $notification->reference,
                'amount' => $notification->amountPaid,
                'fee' => $notification->fee,
                'balance_after' => (float) $wallet->balance,
            ]);

            DepositSucceeded::dispatch($transaction->fresh());

            if ($wasFirstDeposit) {
                CreditReferralCommission::dispatch($transaction->user_id, $notification->amountPaid);
            }

            return 'success';
        }, 5);
    }

    public function statusFor(string $reference, string $gateway): ?string
    {
        return Transaction::query()
            ->where('reference', $reference)
            ->where('gateway', $gateway)
            ->value('status');
    }

    public function findFor(string $reference, string $gateway): ?Transaction
    {
        return Transaction::query()
            ->where('reference', $reference)
            ->where('gateway', $gateway)
            ->first();
    }

    /**
     * Attributes shared by every non-successful settlement path.
     */
    private function failureAttributes(Transaction $transaction, PaymentNotification $notification, string $paymentStatus): array
    {
        return [
            'status' => Transaction::STATUS_FAILED,
            'payment_status' => $paymentStatus,
            'currency' => $notification->currency ?: $transaction->currency,
            'fee' => $notification->fee,
            'gateway_reference' => $notification->gatewayReference ?: $transaction->gateway_reference,
            'gateway_response' => $notification->raw,
            'webhook_payload' => $notification->raw,
            'payment_method' => $notification->method?->value ?? $transaction->payment_method,
            'meta' => array_merge((array) $transaction->meta, [
                'failure_reason' => $notification->reason ?: $paymentStatus,
            ]),
        ];
    }

    private function testMode(string $gateway): bool
    {
        return $gateway === 'monnify'
            ? PaymentSettings::monnifyTestMode()
            : PaymentSettings::paystackTestMode();
    }
}
