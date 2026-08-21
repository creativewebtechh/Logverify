<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingDeposit(float $amount): array
    {
        $user = User::create([
            'name' => 'Paystack Customer',
            'email' => 'paystack@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'currency' => 'NGN',
            'balance_after' => 0,
            'status' => Transaction::STATUS_PENDING,
            'reference' => 'PSTK-TEST-REF-1',
            'gateway' => 'paystack',
            'description' => 'Wallet funding',
            'meta' => ['test_mode' => false],
        ]);

        return [$user, $transaction];
    }

    private function fakeVerifySuccess(): void
    {
        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'success',
                    'reference' => 'PSTK-TEST-REF-1',
                    'amount' => 250000,
                    'fees' => 2500,
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'id' => 99,
                ],
            ], 200),
        ]);
    }

    public function test_webhook_returns_sandbox_in_test_mode(): void
    {
        config(['paystack.test_mode' => true]);

        $this->postJson('/paystack/webhook', ['event' => 'charge.success'])
            ->assertOk()
            ->assertJson(['status' => 'sandbox']);
    }

    public function test_webhook_rejects_invalid_signature_and_logs_it(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        $this->postJson('/paystack/webhook', [
            'event' => 'charge.success',
            'data' => ['reference' => 'PSTK-TEST-REF-1', 'amount' => 250000],
        ], ['x-paystack-signature' => 'forged-signature'])
            ->assertStatus(401)
            ->assertJson(['status' => 'invalid_signature']);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'paystack',
            'status' => WebhookLog::STATUS_INVALID_SIGNATURE,
            'response_status' => 401,
        ]);
    }

    public function test_webhook_credits_wallet_on_successful_transaction(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        [$user, $transaction] = $this->makePendingDeposit(2500);
        $this->fakeVerifySuccess();

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => 'PSTK-TEST-REF-1', 'amount' => 250000],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->postJson('/paystack/webhook', json_decode($payload, true), ['x-paystack-signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_SUCCESS, $transaction->payment_status);
        $this->assertSame(2500.0, (float) $transaction->amount);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
        $this->assertSame('99', $transaction->gateway_reference);
        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'paystack',
            'reference' => 'PSTK-TEST-REF-1',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_webhook_ignores_unknown_reference(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => 'DOES-NOT-EXIST', 'amount' => 100000],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->postJson('/paystack/webhook', json_decode($payload, true), ['x-paystack-signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'paystack',
            'reference' => 'DOES-NOT-EXIST',
            'status' => WebhookLog::STATUS_IGNORED,
        ]);
    }

    public function test_webhook_credits_from_signed_payload_when_verify_api_is_down(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        [$user, $transaction] = $this->makePendingDeposit(2500);

        Http::fake(['api.paystack.co/*' => Http::response([], 500)]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PSTK-TEST-REF-1',
                'amount' => 250000,
                'fees' => 2500,
                'channel' => 'card',
                'id' => 99,
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->postJson('/paystack/webhook', json_decode($payload, true), ['x-paystack-signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'paystack',
            'reference' => 'PSTK-TEST-REF-1',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_duplicate_webhook_credits_wallet_only_once(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        [$user, $transaction] = $this->makePendingDeposit(2500);
        $this->fakeVerifySuccess();

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PSTK-TEST-REF-1',
                'amount' => 250000,
                'fees' => 2500,
                'channel' => 'card',
                'id' => 99,
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');
        $headers = ['x-paystack-signature' => $signature];

        $this->postJson('/paystack/webhook', json_decode($payload, true), $headers)
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->postJson('/paystack/webhook', json_decode($payload, true), $headers)
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(25.0, (float) $transaction->fee);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
    }
}
