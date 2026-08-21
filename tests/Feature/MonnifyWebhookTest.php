<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonnifyWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingDeposit(float $amount): array
    {
        $user = User::create([
            'name' => 'Monnify Customer',
            'email' => 'monnify@logverify.test',
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
            'reference' => 'MNFY-TEST-REF-1',
            'gateway' => 'monnify',
            'description' => 'Wallet funding',
            'meta' => ['test_mode' => false],
        ]);

        return [$user, $transaction];
    }

    private function fakeVerifySuccess(): void
    {
        Http::fake([
            '*' => function (Request $request) {
                if (str_contains($request->url(), '/auth/login')) {
                    return Http::response([
                        'requestSuccessful' => true,
                        'responseBody' => ['accessToken' => 'test-token'],
                    ], 200);
                }

                return Http::response([
                    'requestSuccessful' => true,
                    'responseBody' => [
                        'transactionReference' => 'MNFY|20260803|000123',
                        'paymentReference' => 'MNFY-TEST-REF-1',
                        'amountPaid' => 250000,
                        'settlementAmount' => 247000,
                        'paymentStatus' => 'PAID',
                        'paymentMethod' => 'CARD',
                        'currency' => 'NGN',
                    ],
                ], 200);
            },
        ]);
    }

    public function test_webhook_returns_sandbox_in_test_mode(): void
    {
        config(['monnify.test_mode' => true]);

        $this->postJson(route('monnify.webhook'), ['eventType' => 'SUCCESSFUL_TRANSACTION'])
            ->assertOk()
            ->assertJson(['status' => 'sandbox']);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['monnify.test_mode' => false, 'monnify.client_secret' => 'secret-key']);

        $this->postJson(route('monnify.webhook'), [
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => ['paymentReference' => 'MNFY-TEST-REF-1', 'amountPaid' => 250000],
        ], ['monnify-signature' => 'forged-signature'])
            ->assertStatus(401)
            ->assertJson(['status' => 'invalid_signature']);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'monnify',
            'status' => WebhookLog::STATUS_INVALID_SIGNATURE,
            'response_status' => 401,
        ]);
    }

    public function test_webhook_credits_wallet_on_successful_transaction(): void
    {
        config([
            'monnify.test_mode' => false,
            'monnify.client_key' => 'client-key',
            'monnify.client_secret' => 'secret-key',
            'monnify.contract_code' => 'MK_TEST',
            'monnify.base_url' => 'https://sandbox.monnify.com',
        ]);

        [$user, $transaction] = $this->makePendingDeposit(2500);
        $this->fakeVerifySuccess();

        $payload = json_encode([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'transactionReference' => 'MNFY|20260803|000123',
                'paymentReference' => 'MNFY-TEST-REF-1',
                'amountPaid' => 250000,
                'paymentStatus' => 'PAID',
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->postJson(route('monnify.webhook'), json_decode($payload, true), ['monnify-signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_SUCCESS, $transaction->payment_status);
        $this->assertSame(2500.0, (float) $transaction->amount);
        $this->assertSame(30.0, (float) $transaction->fee);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame('MNFY|20260803|000123', $transaction->gateway_reference);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'monnify',
            'reference' => 'MNFY-TEST-REF-1',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_webhook_ignores_unknown_reference(): void
    {
        config(['monnify.test_mode' => false, 'monnify.client_secret' => 'secret-key']);

        $payload = json_encode([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => ['paymentReference' => 'DOES-NOT-EXIST', 'amountPaid' => 100000],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->postJson(route('monnify.webhook'), json_decode($payload, true), ['monnify-signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'monnify',
            'reference' => 'DOES-NOT-EXIST',
            'status' => WebhookLog::STATUS_IGNORED,
        ]);
    }

    public function test_webhook_credits_from_signed_payload_when_verify_api_is_down(): void
    {
        config([
            'monnify.test_mode' => false,
            'monnify.client_key' => 'client-key',
            'monnify.client_secret' => 'secret-key',
            'monnify.contract_code' => 'MK_TEST',
            'monnify.base_url' => 'https://sandbox.monnify.com',
        ]);

        [$user, $transaction] = $this->makePendingDeposit(2500);

        Http::fake([
            '*' => function (Request $request) {
                if (str_contains($request->url(), '/auth/login')) {
                    return Http::response([
                        'requestSuccessful' => true,
                        'responseBody' => ['accessToken' => 'test-token'],
                    ], 200);
                }

                return Http::response(['requestSuccessful' => false], 500);
            },
        ]);

        $payload = json_encode([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'transactionReference' => 'MNFY|20260803|000456',
                'paymentReference' => 'MNFY-TEST-REF-1',
                'amountPaid' => 250000,
                'settlementAmount' => 247000,
                'paymentStatus' => 'PAID',
                'paymentMethod' => 'CARD',
                'currency' => 'NGN',
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');

        $this->postJson(route('monnify.webhook'), json_decode($payload, true), ['monnify-signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
        $this->assertDatabaseHas('webhook_logs', [
            'gateway' => 'monnify',
            'reference' => 'MNFY-TEST-REF-1',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_duplicate_webhook_credits_wallet_only_once(): void
    {
        config([
            'monnify.test_mode' => false,
            'monnify.client_key' => 'client-key',
            'monnify.client_secret' => 'secret-key',
            'monnify.contract_code' => 'MK_TEST',
            'monnify.base_url' => 'https://sandbox.monnify.com',
        ]);

        [$user, $transaction] = $this->makePendingDeposit(2500);
        $this->fakeVerifySuccess();

        $payload = json_encode([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'transactionReference' => 'MNFY|20260803|000123',
                'paymentReference' => 'MNFY-TEST-REF-1',
                'amountPaid' => 250000,
                'paymentStatus' => 'PAID',
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'secret-key');
        $headers = ['monnify-signature' => $signature];

        $this->postJson(route('monnify.webhook'), json_decode($payload, true), $headers)
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->postJson(route('monnify.webhook'), json_decode($payload, true), $headers)
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(30.0, (float) $transaction->fee);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame('MNFY|20260803|000123', $transaction->gateway_reference);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
    }
}
