<?php

namespace Tests\Feature;

use App\Livewire\Wallet\Fund;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WalletFundingTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Wallet Customer',
            'email' => 'wallet@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    public function test_fund_page_captures_pending_deposit_with_method_and_context(): void
    {
        config([
            'paystack.test_mode' => false,
            'paystack.secret_key' => 'sk_test',
            'paystack.public_key' => 'pk_test',
        ]);

        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.test/abc',
                    'access_code' => 'CODE_123',
                ],
            ], 200),
        ]);

        $user = $this->user();

        Livewire::actingAs($user)
            ->test(Fund::class)
            ->set('amount', 2500)
            ->set('paymentMethod', 'card')
            ->call('initialize')
            ->assertHasNoErrors()
            ->assertSet('intentCreated', true)
            ->assertSet('redirectUrl', 'https://checkout.paystack.test/abc');

        $transaction = Transaction::where('user_id', $user->id)->first();

        $this->assertNotNull($transaction);
        $this->assertSame(Transaction::TYPE_DEPOSIT, $transaction->type);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_PENDING, $transaction->payment_status);
        $this->assertSame('paystack', $transaction->gateway);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame(2500.0, (float) $transaction->amount);
        $this->assertNotNull($transaction->reference);
        $this->assertSame(0.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_fund_page_rejects_method_not_supported_by_gateway(): void
    {
        config(['paystack.test_mode' => false]);

        $user = $this->user();

        Livewire::actingAs($user)
            ->test(Fund::class)
            ->set('gateway', 'monnify')
            ->set('amount', 500)
            ->set('paymentMethod', 'qr')
            ->call('initialize')
            ->assertHasErrors('paymentMethod');

        $this->assertDatabaseMissing('transactions', ['user_id' => $user->id]);
    }

    public function test_fund_page_captures_monnify_pending_deposit(): void
    {
        config([
            'monnify.test_mode' => false,
            'monnify.client_key' => 'client-key',
            'monnify.client_secret' => 'secret-key',
            'monnify.contract_code' => 'MK_TEST',
            'monnify.base_url' => 'https://sandbox.monnify.com',
        ]);

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
                        'checkoutUrl' => 'https://checkout.monnify.com/pay/abc',
                        'transactionReference' => 'MNFY|20260803|000777',
                    ],
                ], 200);
            },
        ]);

        $user = $this->user();

        Livewire::actingAs($user)
            ->test(Fund::class)
            ->set('gateway', 'monnify')
            ->set('amount', 1500)
            ->set('paymentMethod', 'card')
            ->call('initialize')
            ->assertHasNoErrors()
            ->assertSet('intentCreated', true)
            ->assertSet('redirectUrl', 'https://checkout.monnify.com/pay/abc');

        $transaction = Transaction::where('user_id', $user->id)->first();

        $this->assertNotNull($transaction);
        $this->assertSame('monnify', $transaction->gateway);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(1500.0, (float) $transaction->amount);
        $this->assertSame(0.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_fund_page_records_sandbox_intent_when_unconfigured(): void
    {
        config(['paystack.test_mode' => true]);

        $user = $this->user();

        Livewire::actingAs($user)
            ->test(Fund::class)
            ->set('amount', 500)
            ->call('initialize')
            ->assertSet('intentCreated', true)
            ->assertSet('sandbox', true)
            ->assertSet('redirectUrl', null);

        $transaction = Transaction::where('user_id', $user->id)->first();

        $this->assertNotNull($transaction);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
    }

    public function test_check_status_verifies_and_credits_wallet(): void
    {
        config([
            'paystack.test_mode' => false,
            'paystack.secret_key' => 'sk_test',
            'paystack.public_key' => 'pk_test',
        ]);

        Http::fake([
            '*' => function (Request $request) {
                if (str_contains($request->url(), '/transaction/initialize')) {
                    return Http::response([
                        'status' => true,
                        'message' => 'Authorization URL created',
                        'data' => [
                            'authorization_url' => 'https://checkout.paystack.test/check',
                            'access_code' => 'CODE_CHECK',
                        ],
                    ], 200);
                }

                $reference = basename($request->url());

                return Http::response([
                    'status' => true,
                    'message' => 'Verification successful',
                    'data' => [
                        'status' => 'success',
                        'reference' => $reference,
                        'amount' => 250000,
                        'fees' => 2500,
                        'channel' => 'card',
                        'currency' => 'NGN',
                        'id' => 99,
                    ],
                ], 200);
            },
        ]);

        $user = $this->user();

        Livewire::actingAs($user)
            ->test(Fund::class)
            ->set('amount', 2500)
            ->set('paymentMethod', 'card')
            ->call('initialize')
            ->assertSet('intentCreated', true)
            ->call('checkStatus')
            ->assertSet('receipt.gateway', 'paystack')
            ->assertHasNoErrors();

        $transaction = Transaction::where('user_id', $user->id)->first();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_check_status_shows_cancelled_message_without_crediting(): void
    {
        config([
            'paystack.test_mode' => false,
            'paystack.secret_key' => 'sk_test',
            'paystack.public_key' => 'pk_test',
        ]);

        Http::fake([
            '*' => function (Request $request) {
                if (str_contains($request->url(), '/transaction/initialize')) {
                    return Http::response([
                        'status' => true,
                        'message' => 'Authorization URL created',
                        'data' => [
                            'authorization_url' => 'https://checkout.paystack.test/cancel',
                            'access_code' => 'CODE_CANCEL',
                        ],
                    ], 200);
                }

                return Http::response([
                    'status' => true,
                    'message' => 'Verification failed',
                    'data' => [
                        'status' => 'abandoned',
                        'reference' => basename($request->url()),
                        'amount' => 100000,
                    ],
                ], 200);
            },
        ]);

        $user = $this->user();

        Livewire::actingAs($user)
            ->test(Fund::class)
            ->set('amount', 1000)
            ->set('paymentMethod', 'card')
            ->call('initialize')
            ->assertSet('intentCreated', true)
            ->call('checkStatus')
            ->assertSet('intentCreated', false)
            ->assertHasNoErrors();

        $transaction = Transaction::where('user_id', $user->id)->first();

        $this->assertSame(Transaction::STATUS_FAILED, $transaction->fresh()->status);
        $this->assertSame(0.0, (float) $user->fresh()->wallet->balance);
    }
}
