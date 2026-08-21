<?php

namespace Tests\Feature;

use App\Livewire\Services\Accounts;
use App\Livewire\Services\BoostOrderForm;
use App\Livewire\Services\Numbers;
use App\Livewire\Services\Tools;
use App\Models\Account;
use App\Models\NumberService;
use App\Models\Order;
use App\Models\Service;
use App\Models\Tool;
use App\Models\User;
use App\Services\PinService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PinEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Customer',
            'email' => 'customer@pin.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'transaction_pin' => bcrypt('1234'),
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

    private function makeTool(): Tool
    {
        return Tool::create([
            'name' => 'Auto Poster',
            'slug' => 'auto-poster',
            'description' => 'Automate your posts',
            'price' => 2000,
            'category' => 'automation',
            'status' => 'active',
        ]);
    }

    private function makeNumberService(): NumberService
    {
        return NumberService::create([
            'catalog_key' => 'manual:NG:wa',
            'country_code' => 'NG',
            'country_name' => 'Nigeria',
            'name' => 'WhatsApp',
            'slug' => 'whatsapp',
            'category' => 'sms',
            'price' => 1500,
            'cost' => 1500,
            'markup_percent' => 0,
            'status' => NumberService::STATUS_ACTIVE,
            'hidden' => false,
        ]);
    }

    private function makeBoostService(): Service
    {
        return Service::create([
            'name' => 'Instagram Followers',
            'slug' => 'ig-followers',
            'description' => 'Fast and safe follower growth',
            'type' => 'social',
            'platform' => 'instagram',
            'price_per_unit' => 0.5,
            'min_qty' => 50,
            'max_qty' => 10000,
            'provider_service_id' => '',
            'status' => 'active',
        ]);
    }

    public function test_account_purchase_requires_a_valid_transaction_pin(): void
    {
        Http::fake();

        $account = $this->makeAccount();

        Livewire::actingAs($this->user)
            ->test(Accounts::class)
            ->call('buy', $account->id)
            ->assertSet('showPinModal', true)
            ->set('pin', '0000')
            ->call('confirmPurchase')
            ->assertHasErrors('pin')
            ->assertSet('showPinModal', true);

        $this->assertSame(0, Order::count());

        Livewire::actingAs($this->user)
            ->test(Accounts::class)
            ->call('buy', $account->id)
            ->set('pin', '1234')
            ->call('confirmPurchase')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'orderable_type' => Account::class,
            'orderable_id' => $account->id,
            'status' => Order::STATUS_PAID,
            'total' => 8000.0,
        ]);
        $this->assertSame(42000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_tool_purchase_requires_a_valid_transaction_pin(): void
    {
        Http::fake();

        $tool = $this->makeTool();

        Livewire::actingAs($this->user)
            ->test(Tools::class)
            ->call('buy', $tool->id)
            ->assertSet('showPinModal', true)
            ->set('pin', '1234')
            ->call('confirmPurchase')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'orderable_type' => Tool::class,
            'orderable_id' => $tool->id,
            'status' => Order::STATUS_PAID,
            'total' => 2000.0,
        ]);
        $this->assertSame(48000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_number_purchase_requires_a_valid_transaction_pin(): void
    {
        Http::fake();

        $service = $this->makeNumberService();

        Livewire::actingAs($this->user)
            ->test(Numbers::class)
            ->call('confirm', $service->id)
            ->assertSet('confirmingId', $service->id)
            ->call('openPin', $service->id)
            ->assertSet('showPinModal', true)
            ->set('pin', '1234')
            ->call('confirmPurchase')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'orderable_type' => NumberService::class,
            'orderable_id' => $service->id,
            'status' => Order::STATUS_PROCESSING,
            'total' => 1500.0,
        ]);
        $this->assertSame(48500.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_transaction_pin_locks_out_after_repeated_failures(): void
    {
        Http::fake();

        $service = $this->makeBoostService();

        $component = Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('quantity', 100)
            ->set('target', 'https://instagram.com/testhandle')
            ->call('placeOrder')
            ->assertSet('showPinModal', true);

        foreach (range(1, 5) as $attempt) {
            $component->set('pin', '9999')->call('confirmPurchase');
        }

        $this->assertTrue(app(PinService::class)->isLocked($this->user));

        $component->set('pin', '1234')->call('confirmPurchase')->assertHasErrors('pin');

        $this->assertSame(0, Order::count());
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }
}
