<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageAccounts;
use App\Models\Account;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Customer',
            'email' => 'customer@accounts.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($this->user);
        app(WalletService::class)->credit($this->user, 50000, 'deposit', 'Test funding');
    }

    private function makeAccount(): Account
    {
        return Account::create([
            'platform' => 'instagram',
            'title' => 'Aged Instagram account',
            'description' => '2019 account with 5k followers',
            'price' => 8000,
            'status' => 'available',
        ]);
    }

    public function test_account_purchase_without_provider_is_simulated(): void
    {
        Http::fake();

        $account = $this->makeAccount();
        $order = app(OrderService::class)->buyAccount($this->user, $account);

        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertSame('sold', $account->fresh()->status);
        $this->assertSame('Aged Instagram account', $order->title);
        Http::assertNothingSent();
    }

    public function test_account_purchase_delivers_manual_credentials(): void
    {
        $account = $this->makeAccount();
        $account->update([
            'credentials' => [
                'username' => 'ig_user_99',
                'password' => 'p@ssw0rd!',
                'email' => 'ig99@example.com',
                'two_fa' => 'JBSWY3DPEHPK3PXP',
                'backup_codes' => '1111-2222, 3333-4444',
            ],
        ]);

        $order = app(OrderService::class)->buyAccount($this->user, $account);

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame('ig_user_99', $order->meta['account']['username']);
        $this->assertSame('p@ssw0rd!', $order->meta['account']['password']);
        $this->assertSame('ig99@example.com', $order->meta['account']['email']);
        $this->assertSame('JBSWY3DPEHPK3PXP', $order->meta['account']['two_fa']);
        $this->assertSame('1111-2222, 3333-4444', $order->meta['account']['backup_codes']);
        $this->assertSame('sold', $account->fresh()->status);
        $this->assertSame(42000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_account_purchase_decrements_stock(): void
    {
        $account = $this->makeAccount();
        $account->update(['stock' => 3, 'credentials' => ['email' => 'a@example.com']]);

        $order = app(OrderService::class)->buyAccount($this->user, $account);

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame(2, $account->fresh()->stock);
        $this->assertSame('available', $account->fresh()->status);
    }

    public function test_account_purchase_out_of_stock_is_rejected(): void
    {
        $account = $this->makeAccount();
        $account->update(['stock' => 0]);

        try {
            app(OrderService::class)->buyAccount($this->user, $account);
            $this->fail('Expected a DomainException for an out-of-stock account.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('out of stock', strtolower($e->getMessage()));
        }

        $this->assertSame('available', $account->fresh()->status);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_can_add_account_from_panel(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@accounts.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageAccounts::class)
            ->set('platform', 'whatsapp')
            ->set('title', 'Business WhatsApp account')
            ->set('description', 'With email access')
            ->set('price', 12000)
            ->set('provider_service_id', '77')
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('accounts', [
            'platform' => 'whatsapp',
            'title' => 'Business WhatsApp account',
            'provider_service_id' => '77',
            'status' => 'available',
        ]);
    }

    public function test_account_purchase_delivers_manual_credentials_without_provider(): void
    {
        $account = $this->makeAccount();
        $account->update([
            'meta' => ['credentials' => ['email' => 'seller@example.com', 'password' => 'supersecret', 'phone' => '+2348000000000']],
        ]);

        $order = app(OrderService::class)->buyAccount($this->user, $account);

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame('seller@example.com', $order->meta['account']['email']);
        $this->assertSame('supersecret', $order->meta['account']['password']);
        $this->assertSame('+2348000000000', $order->meta['account']['phone']);
        $this->assertSame('sold', $account->fresh()->status);
    }

    public function test_admin_can_edit_account_listing_from_panel(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin3@accounts.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $account = $this->makeAccount();

        Livewire::actingAs($admin)
            ->test(ManageAccounts::class)
            ->call('edit', $account->id)
            ->set('title', 'Updated aged account')
            ->set('status', 'pending')
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'title' => 'Updated aged account',
            'status' => 'pending',
        ]);
    }
}
