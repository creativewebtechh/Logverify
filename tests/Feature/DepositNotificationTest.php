<?php

namespace Tests\Feature;

use App\Mail\DepositFunded;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Payments\Data\PaymentNotification;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DepositNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function successfulDeposit(): Transaction
    {
        $user = User::create([
            'name' => 'Notified Customer',
            'email' => 'notified@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => 2500,
            'currency' => 'NGN',
            'balance_after' => 0,
            'status' => Transaction::STATUS_PENDING,
            'reference' => 'LV-NOTIFY-1',
            'gateway' => 'paystack',
            'description' => 'Wallet funding',
        ]);

        $notification = new PaymentNotification(
            gateway: 'paystack',
            reference: 'LV-NOTIFY-1',
            status: PaymentNotification::STATUS_SUCCESS,
            amountPaid: 2500.0,
            fee: 25.0,
            currency: 'NGN',
            raw: ['amount' => 250000],
        );

        app(TransactionService::class)->fulfil('paystack', $notification);

        return $transaction->fresh();
    }

    public function test_successful_deposit_creates_in_app_notification(): void
    {
        $transaction = $this->successfulDeposit();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $transaction->user_id,
            'type' => 'success',
        ]);

        $notification = Notification::forUser($transaction->user_id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('2,500', $notification->message);
    }

    public function test_successful_deposit_sends_deposit_funded_email(): void
    {
        Mail::fake();

        $transaction = $this->successfulDeposit();

        Mail::assertSent(DepositFunded::class, function (DepositFunded $mail) use ($transaction) {
            return $mail->hasTo($transaction->user->email)
                && $mail->transaction->id === $transaction->id;
        });
    }
}
