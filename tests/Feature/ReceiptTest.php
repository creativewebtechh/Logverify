<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeposit(User $user, string $reference): Transaction
    {
        app(WalletService::class)->getOrCreateWallet($user);

        return Transaction::create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => 2500,
            'currency' => 'NGN',
            'fee' => 25,
            'balance_after' => 2500,
            'status' => Transaction::STATUS_SUCCESS,
            'payment_status' => Transaction::PAYMENT_SUCCESS,
            'reference' => $reference,
            'gateway_reference' => 'PSK-RCPT-1',
            'gateway' => 'paystack',
            'payment_method' => 'card',
            'description' => 'Wallet funding',
            'paid_at' => now(),
        ]);
    }

    private function user(string $email, ?string $role = null): User
    {
        $attributes = [
            'name' => 'Receipt Customer',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];

        if ($role !== null) {
            $attributes['role'] = $role;
        }

        return User::create($attributes);
    }

    public function test_owner_can_view_receipt(): void
    {
        $user = $this->user('owner@logverify.test');
        $transaction = $this->makeDeposit($user, 'LV-RCPT-1');

        $this->actingAs($user)
            ->get(route('wallet.transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee('LV-RCPT-1')
            ->assertSee('PSK-RCPT-1')
            ->assertSee('Card');
    }

    public function test_other_user_cannot_view_receipt(): void
    {
        $owner = $this->user('owner2@logverify.test');
        $other = $this->user('intruder@logverify.test');
        $transaction = $this->makeDeposit($owner, 'LV-RCPT-2');

        $this->actingAs($other)
            ->get(route('wallet.transactions.receipt', $transaction))
            ->assertStatus(403);
    }

    public function test_admin_can_view_any_receipt(): void
    {
        $owner = $this->user('owner3@logverify.test');
        $admin = $this->user('admin@logverify.test', 'admin');
        $transaction = $this->makeDeposit($owner, 'LV-RCPT-3');

        $this->actingAs($admin)
            ->get(route('wallet.transactions.receipt', $transaction))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = $this->user('owner4@logverify.test');
        $transaction = $this->makeDeposit($owner, 'LV-RCPT-4');

        $this->get(route('wallet.transactions.receipt', $transaction))
            ->assertRedirect(route('login'));
    }
}
