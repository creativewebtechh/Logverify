<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageNumberServices;
use App\Livewire\Admin\ManageOrders;
use App\Livewire\Admin\SmsDashboard;
use App\Models\NumberService;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Numbers\NumberPurchaseService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NumberLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Customer',
            'email' => 'customer@lifecycle.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($this->user);
        app(WalletService::class)->credit($this->user, 50000, 'deposit', 'Test funding');
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

    private function makeGrizzlyProvider(): Provider
    {
        return Provider::create([
            'channel' => 'numbers',
            'name' => 'Grizzly SMS',
            'driver' => 'grizzly',
            'base_url' => 'https://api.grizzlysms.com',
            'api_key' => 'griz-key',
            'priority' => 0,
        ]);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@lifecycle.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_duplicate_purchase_within_window_is_blocked(): void
    {
        Http::fake();

        $service = $this->makeNumberService();

        app(NumberPurchaseService::class)->purchase($this->user, $service);

        try {
            app(NumberPurchaseService::class)->purchase($this->user, $service);
            $this->fail('A duplicate purchase must be rejected.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Check My Numbers', $e->getMessage());
        }

        $this->assertSame(1, Order::count());
    }

    public function test_expired_activation_is_refunded_automatically(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(48500.0, (float) $this->user->wallet->fresh()->balance);

        $order->update(['expires_at' => now()->subMinute()]);

        $count = app(NumberPurchaseService::class)->releaseExpired();

        $this->assertSame(1, $count);

        $order = $order->fresh();

        $this->assertSame(Order::STATUS_EXPIRED, $order->status);
        $this->assertSame(Order::SMS_EXPIRED, $order->sms_status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'refund',
            'amount' => '1500.00',
        ]);
    }

    public function test_double_refund_is_idempotent(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        app(NumberPurchaseService::class)->expire($order, 'First expiry');
        app(NumberPurchaseService::class)->expire($order, 'Second expiry');

        $order = $order->fresh();

        $this->assertSame(Order::STATUS_EXPIRED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);

        $this->assertSame(1, Transaction::query()->where('type', 'refund')->count());
    }

    public function test_customer_can_cancel_and_gets_refunded(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $order = app(NumberPurchaseService::class)->cancel($this->user, $order, 'I no longer need this number');

        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::SMS_CANCELLED, $order->sms_status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_poll_completes_order_when_sms_arrives(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::sequence()
                ->push('ACCESS_NUMBER:12345678:79991112233', 200)
                ->push('STATUS_OK', 200)
                ->push('CODE:482913', 200),
        ]);

        $this->makeGrizzlyProvider();

        $service = $this->makeNumberService();
        $service->update(['provider_service_id' => 'wa', 'provider_country_id' => '47']);

        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);

        $order = app(NumberPurchaseService::class)->poll($order);

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame(Order::SMS_RECEIVED, $order->sms_status);
        $this->assertSame('482913', $order->sms_code);
        $this->assertNotNull($order->completed_at);
        $this->assertNull($order->expires_at);
        $this->assertSame(48500.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_poll_refunds_when_provider_reports_no_sms(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::sequence()
                ->push('ACCESS_NUMBER:12345678:79991112233', 200)
                ->push('STATUS_NO_ACTIVATION', 200),
        ]);

        $this->makeGrizzlyProvider();

        $service = $this->makeNumberService();
        $service->update(['provider_service_id' => 'wa', 'provider_country_id' => '47']);

        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);
        $order = app(NumberPurchaseService::class)->poll($order);

        $this->assertSame(Order::STATUS_EXPIRED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_poll_does_not_double_complete_an_already_received_order(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::sequence()
                ->push('ACCESS_NUMBER:12345678:79991112233', 200)
                ->push('STATUS_OK', 200)
                ->push('CODE:482913', 200),
        ]);

        $this->makeGrizzlyProvider();

        $service = $this->makeNumberService();
        $service->update(['provider_service_id' => 'wa', 'provider_country_id' => '47']);

        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);
        $order = app(NumberPurchaseService::class)->poll($order);

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);

        $again = app(NumberPurchaseService::class)->poll($order);

        $this->assertSame(Order::STATUS_COMPLETED, $again->status);
        $this->assertSame(Order::SMS_RECEIVED, $again->sms_status);
        $this->assertSame('482913', $again->sms_code);
    }

    public function test_admin_sms_dashboard_renders(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        app(NumberPurchaseService::class)->purchase($this->user, $service);

        Livewire::actingAs($this->makeAdmin())
            ->test(SmsDashboard::class)
            ->assertStatus(200)
            ->assertSee('SMS Dashboard')
            ->assertSee('WhatsApp');
    }

    public function test_admin_number_services_can_edit_and_reprice(): void
    {
        $service = $this->makeNumberService();
        $service->update(['markup_percent' => null, 'price' => 1000, 'cost' => 1000]);

        Livewire::actingAs($this->makeAdmin())
            ->test(ManageNumberServices::class)
            ->call('edit', $service->id)
            ->set('markup_percent', '50')
            ->call('save')
            ->assertHasNoErrors();

        $service = $service->fresh();

        $this->assertSame('50.00', (string) $service->markup_percent);
        $this->assertSame('1500.0000', (string) $service->price);

        $this->assertDatabaseHas('number_price_history', [
            'number_service_id' => $service->id,
            'new_price' => '1500.0000',
            'reason' => 'manual',
        ]);
    }

    public function test_admin_number_services_bulk_pricing(): void
    {
        $first = $this->makeNumberService();
        $first->update(['markup_percent' => null, 'price' => 1000, 'cost' => 1000]);

        $second = NumberService::create([
            'catalog_key' => 'manual:GH:wa',
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'name' => 'WhatsApp',
            'slug' => 'whatsapp-gh',
            'category' => 'sms',
            'price' => 2000,
            'cost' => 2000,
            'markup_percent' => null,
            'status' => NumberService::STATUS_ACTIVE,
            'hidden' => false,
        ]);
        Livewire::actingAs($this->makeAdmin())
            ->test(ManageNumberServices::class)
            ->set('selected', [$first->id, $second->id])
            ->set('bulk_markup_percent', '10')
            ->call('applyBulk')
            ->assertHasNoErrors();

        $this->assertSame('1100.0000', (string) $first->fresh()->price);
        $this->assertSame('2200.0000', (string) $second->fresh()->price);
    }

    public function test_admin_can_cancel_number_order_from_orders_panel(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        Livewire::actingAs($this->makeAdmin())
            ->test(ManageOrders::class)
            ->call('cancelNumber', $order->id)
            ->assertHasNoErrors();

        $order = $order->fresh();

        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_admin_can_complete_number_with_manual_sms_code(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        Livewire::actingAs($this->makeAdmin())
            ->test(ManageOrders::class)
            ->call('completeNumber', $order->id, '112233')
            ->assertHasNoErrors();

        $order = $order->fresh();

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame(Order::SMS_RECEIVED, $order->sms_status);
        $this->assertSame('112233', $order->sms_code);
    }
}
