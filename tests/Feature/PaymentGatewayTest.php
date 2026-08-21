<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Payments\Data\PaymentNotification;
use App\Payments\GatewayRegistry;
use App\Payments\MonnifyGateway;
use App\Payments\PaymentMethod;
use App\Payments\PaystackGateway;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingDeposit(float $amount, string $gateway): array
    {
        $user = User::create([
            'name' => 'Gateway Customer',
            'email' => 'gateway@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'balance_after' => 0,
            'status' => Transaction::STATUS_PENDING,
            'payment_status' => Transaction::PAYMENT_PENDING,
            'reference' => 'LV-GATEWAY-'.$gateway,
            'gateway' => $gateway,
            'description' => 'Wallet funding',
        ]);

        return [$user, $transaction];
    }

    public function test_registry_resolves_supported_gateways(): void
    {
        $registry = app(GatewayRegistry::class);

        $this->assertSame('paystack', $registry->gateway('paystack')->name());
        $this->assertSame('monnify', $registry->gateway('monnify')->name());
        $this->assertCount(2, $registry->all());
    }

    public function test_gateways_expose_supported_methods(): void
    {
        $paystack = new PaystackGateway;
        $monnify = new MonnifyGateway;

        $this->assertTrue($paystack->supportsMethod(PaymentMethod::Card));
        $this->assertTrue($paystack->supportsMethod(PaymentMethod::BankTransfer));
        $this->assertTrue($paystack->supportsMethod(PaymentMethod::Ussd));
        $this->assertTrue($paystack->supportsMethod(PaymentMethod::Qr));
        $this->assertTrue($paystack->supportsMethod(PaymentMethod::MobileMoney));

        $this->assertTrue($monnify->supportsMethod(PaymentMethod::Card));
        $this->assertTrue($monnify->supportsMethod(PaymentMethod::BankTransfer));
        $this->assertTrue($monnify->supportsMethod(PaymentMethod::Ussd));
        $this->assertFalse($monnify->supportsMethod(PaymentMethod::Qr));
    }

    public function test_paystack_webhook_parses_to_normalised_notification(): void
    {
        $gateway = new PaystackGateway;

        $notification = $gateway->parseWebhook([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'LV-1',
                'amount' => 250000,
                'fees' => 2500,
                'channel' => 'card',
                'currency' => 'NGN',
                'id' => 42,
            ],
        ]);

        $this->assertSame('success', $notification->status);
        $this->assertSame('LV-1', $notification->reference);
        $this->assertSame(2500.0, $notification->amountPaid);
        $this->assertSame(25.0, $notification->fee);
        $this->assertSame(PaymentMethod::Card, $notification->method);
        $this->assertSame('42', $notification->gatewayReference);
    }

    public function test_monnify_webhook_parses_and_computes_fee_from_settlement(): void
    {
        $gateway = new MonnifyGateway;

        $notification = $gateway->parseWebhook([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'transactionReference' => 'MNFY|20260803|000123',
                'paymentReference' => 'LV-2',
                'amountPaid' => 250000,
                'settlementAmount' => 247000,
                'paymentStatus' => 'PAID',
                'paymentMethod' => 'CARD',
            ],
        ]);

        $this->assertSame('success', $notification->status);
        $this->assertSame('LV-2', $notification->reference);
        $this->assertSame(2500.0, $notification->amountPaid);
        $this->assertSame(30.0, $notification->fee);
        $this->assertSame(PaymentMethod::Card, $notification->method);
        $this->assertSame('MNFY|20260803|000123', $notification->gatewayReference);
    }

    public function test_paystack_signature_verification(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        $gateway = new PaystackGateway;
        $payload = json_encode(['event' => 'charge.success']);
        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->assertTrue($gateway->verifyWebhookSignature($payload, $signature));
        $this->assertFalse($gateway->verifyWebhookSignature($payload, 'forged'));
    }

    public function test_monnify_signature_verification_accepts_base64_and_hex(): void
    {
        config(['monnify.test_mode' => false, 'monnify.client_secret' => 'secret-key']);

        $gateway = new MonnifyGateway;
        $payload = json_encode(['eventType' => 'SUCCESSFUL_TRANSACTION']);
        $hex = hash_hmac('sha512', $payload, 'secret-key');
        $base64 = base64_encode(hash_hmac('sha512', $payload, 'secret-key', true));

        $this->assertTrue($gateway->verifyWebhookSignature($payload, $hex));
        $this->assertTrue($gateway->verifyWebhookSignature($payload, $base64));
        $this->assertFalse($gateway->verifyWebhookSignature($payload, 'forged'));
    }

    public function test_fulfil_credits_wallet_idempotently(): void
    {
        config(['paystack.test_mode' => false]);

        [$user, $transaction] = $this->makePendingDeposit(2500, 'paystack');

        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'LV-GATEWAY-paystack',
            status: PaymentNotification::STATUS_SUCCESS,
            amountPaid: 2500.0,
            fee: 25.0,
            gatewayReference: 'PY-1',
            currency: 'NGN',
            method: PaymentMethod::Card,
            raw: ['amount' => 250000],
        );

        $service = app(TransactionService::class);

        $this->assertSame('success', $service->fulfil('paystack', $notification));
        $this->assertSame('already_processed', $service->fulfil('paystack', $notification));

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_SUCCESS, $transaction->payment_status);
        $this->assertSame(2500.0, (float) $transaction->amount);
        $this->assertSame(25.0, (float) $transaction->fee);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame(250000, $transaction->gateway_response['amount'] ?? null);
        $this->assertSame(250000, $transaction->webhook_payload['amount'] ?? null);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_fulfil_rejects_amount_mismatch_without_crediting(): void
    {
        config(['paystack.test_mode' => false]);

        [$user, $transaction] = $this->makePendingDeposit(2500, 'paystack');

        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'LV-GATEWAY-paystack',
            status: PaymentNotification::STATUS_SUCCESS,
            amountPaid: 9999.0,
            raw: ['amount' => 999900],
        );

        $result = app(TransactionService::class)->fulfil('paystack', $notification);

        $this->assertSame('amount_mismatch', $result);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_AMOUNT_MISMATCH, $transaction->payment_status);
        $this->assertSame(0.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_fulfil_marks_failed_notification(): void
    {
        config(['paystack.test_mode' => false]);

        [$user, $transaction] = $this->makePendingDeposit(2500, 'paystack');

        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'LV-GATEWAY-paystack',
            status: PaymentNotification::STATUS_FAILED,
            raw: ['status' => 'failed'],
        );

        $result = app(TransactionService::class)->fulfil('paystack', $notification);

        $this->assertSame('failed', $result);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_FAILED, $transaction->payment_status);
        $this->assertSame(0.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_fulfil_ignores_unknown_reference(): void
    {
        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'DOES-NOT-EXIST',
            status: PaymentNotification::STATUS_SUCCESS,
            amountPaid: 100.0,
        );

        $this->assertSame('not_found', app(TransactionService::class)->fulfil('paystack', $notification));
    }

    public function test_fulfil_rejects_currency_mismatch_without_crediting(): void
    {
        config(['paystack.test_mode' => false]);

        [$user, $transaction] = $this->makePendingDeposit(2500, 'paystack');

        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'LV-GATEWAY-paystack',
            status: PaymentNotification::STATUS_SUCCESS,
            amountPaid: 2500.0,
            currency: 'USD',
            raw: ['currency' => 'USD'],
        );

        $result = app(TransactionService::class)->fulfil('paystack', $notification);

        $this->assertSame('currency_mismatch', $result);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_CURRENCY_MISMATCH, $transaction->payment_status);
        $this->assertSame('USD', $transaction->currency);
        $this->assertSame(0.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_fulfil_stores_gateway_reference_and_failure_reason(): void
    {
        config(['paystack.test_mode' => false]);

        [$user, $transaction] = $this->makePendingDeposit(2500, 'paystack');

        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'LV-GATEWAY-paystack',
            status: PaymentNotification::STATUS_SUCCESS,
            amountPaid: 2500.0,
            gatewayReference: 'PSK-9000',
            currency: 'NGN',
            raw: ['id' => 'PSK-9000'],
        );

        app(TransactionService::class)->fulfil('paystack', $notification);

        $transaction->refresh();

        $this->assertSame('PSK-9000', $transaction->gateway_reference);
        $this->assertSame('NGN', $transaction->currency);
    }

    public function test_gateway_parses_cancelled_reason(): void
    {
        $paystack = new PaystackGateway;

        $cancelled = $paystack->parseWebhook([
            'event' => 'charge.abandoned',
            'data' => ['reference' => 'LV-X', 'amount' => 100000],
        ]);

        $this->assertSame('failed', $cancelled->status);
        $this->assertSame(PaymentNotification::REASON_CANCELLED, $cancelled->reason);
        $this->assertTrue($cancelled->cancelled());

        $monnify = new MonnifyGateway;

        $expired = $monnify->parseWebhook([
            'eventType' => 'FAILED_TRANSACTION',
            'eventData' => ['paymentReference' => 'LV-Y', 'paymentStatus' => 'EXPIRED'],
        ]);

        $this->assertTrue($expired->cancelled());
    }

    public function test_gateways_expose_event_and_reference_helpers(): void
    {
        $paystack = new PaystackGateway;

        $this->assertSame('charge.success', $paystack->eventName(['event' => 'charge.success']));
        $this->assertSame('LV-1', $paystack->transactionReference(['data' => ['reference' => 'LV-1']]));
        $this->assertNull($paystack->transactionReference(['data' => []]));

        $monnify = new MonnifyGateway;

        $this->assertSame('SUCCESSFUL_TRANSACTION', $monnify->eventName(['eventType' => 'SUCCESSFUL_TRANSACTION']));
        $this->assertSame('LV-2', $monnify->transactionReference(['eventData' => ['paymentReference' => 'LV-2']]));
    }
}
