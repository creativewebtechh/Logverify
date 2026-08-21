<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageIntegrations;
use App\Models\NumberService;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Services\Numbers\NumberPurchaseService;
use App\Services\OrderService;
use App\Services\Providers\ProviderFactory;
use App\Services\Providers\ProviderSettings;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Customer',
            'email' => 'customer@provider.test',
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

    private function makeService(): Service
    {
        return Service::create([
            'name' => 'Instagram Followers',
            'slug' => 'ig-followers',
            'description' => 'Boost',
            'type' => 'social',
            'platform' => 'instagram',
            'price_per_unit' => 0.5,
            'min_qty' => 50,
            'max_qty' => 10000,
            'status' => 'active',
        ]);
    }

    public function test_number_purchase_without_provider_is_simulated(): void
    {
        Http::fake();

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Order::SMS_WAITING, $order->sms_status);
        $this->assertNull($order->phone_number);
        $this->assertTrue($order->meta['simulated']);
        $this->assertNotNull($order->expires_at);
        Http::assertNothingSent();
    }

    public function test_number_purchase_calls_provider_and_reserves_number(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'reference' => 'PROV-N-1', 'number' => '+2348099999999'], 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'api_key', 'secret');
        ProviderSettings::set('numbers', 'base_url', 'https://api.example.com');

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Order::SMS_WAITING, $order->sms_status);
        $this->assertSame('+2348099999999', $order->phone_number);
        $this->assertSame('PROV-N-1', $order->provider_reference);
        $this->assertSame(48500.0, (float) $this->user->wallet->fresh()->balance);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.example.com')
                && $request['api_key'] === 'secret'
                && $request['action'] === 'purchase';
        });
    }

    public function test_number_purchase_provider_failure_refunds_and_keeps_service_active(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'error', 'error' => 'No stock'], 400),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'api_key', 'secret');
        ProviderSettings::set('numbers', 'base_url', 'https://api.example.com');

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_FAILED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(NumberService::STATUS_ACTIVE, $service->fresh()->status);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_boost_order_calls_provider_and_stores_reference(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'order' => 'PROV-B-77'], 200),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'generic');
        ProviderSettings::set('boost', 'api_key', 'secret');
        ProviderSettings::set('boost', 'base_url', 'https://api.smmpanel.com');

        $service = $this->makeService();
        $order = app(OrderService::class)->buyBoost($this->user, $service, 100, ['target' => '@handle']);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame('PROV-B-77', $order->meta['provider_reference']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.smmpanel.com')
                && $request['action'] === 'order'
                && $request['quantity'] === 100
                && $request['target'] === '@handle';
        });
    }

    public function test_boost_order_provider_failure_never_charges_wallet(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Invalid service'], 422),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'api_key', 'secret');
        ProviderSettings::set('boost', 'base_url', 'https://api.smmpanel.com');

        $service = $this->makeService();

        try {
            app(OrderService::class)->buyBoost($this->user, $service, 100);
            $this->fail('buyBoost must throw when the provider fails.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('was not charged', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);

        $this->assertDatabaseHas('provider_logs', [
            'channel' => 'boost',
            'type' => 'order',
            'status' => 'failed',
        ]);
    }

    public function test_grizzly_driver_purchases_real_number(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('ACCESS_NUMBER:12345678:79991112233', 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'driver', 'grizzly');
        ProviderSettings::set('numbers', 'api_key', 'griz-key');
        ProviderSettings::set('numbers', 'base_url', 'https://api.grizzlysms.com');

        $service = $this->makeNumberService();
        $service->update(['provider_service_id' => 'wa', 'provider_country_id' => '47']);

        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Order::SMS_WAITING, $order->sms_status);
        $this->assertSame('79991112233', $order->phone_number);
        $this->assertSame('12345678', $order->provider_reference);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'handler_api.php')
                && $request['api_key'] === 'griz-key'
                && $request['action'] === 'getNumber'
                && $request['service'] === 'wa'
                && $request['country'] === '47';
        });
    }

    public function test_grizzly_driver_failure_refunds_and_keeps_service_active(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('NO_NUMBERS', 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'driver', 'grizzly');
        ProviderSettings::set('numbers', 'api_key', 'griz-key');
        ProviderSettings::set('numbers', 'base_url', 'https://api.grizzlysms.com');

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_FAILED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(NumberService::STATUS_ACTIVE, $service->fresh()->status);
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_smmpanel_driver_places_form_encoded_order(): void
    {
        Http::fake([
            'resellersmm.com/*' => Http::response(['order' => 23501], 200),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'smmpanel');
        ProviderSettings::set('boost', 'api_key', 'smm-key');
        ProviderSettings::set('boost', 'base_url', 'https://resellersmm.com');
        ProviderSettings::set('boost', 'order_endpoint', '/api/v2');

        $service = $this->makeService();
        $service->update(['provider_service_id' => '1234']);

        $order = app(OrderService::class)->buyBoost($this->user, $service, 100, ['target' => 'https://instagram.com/handle']);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(23501, $order->meta['provider_reference']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v2')
                && $request->isForm()
                && $request['action'] === 'add'
                && $request['key'] === 'smm-key'
                && $request['service'] === '1234'
                && $request['link'] === 'https://instagram.com/handle'
                && $request['quantity'] === 100;
        });
    }

    public function test_smmpanel_driver_error_body_fails_even_with_http_200(): void
    {
        Http::fake([
            'resellersmm.com/*' => Http::response(['error' => 'Insufficient balance'], 200),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'smmpanel');
        ProviderSettings::set('boost', 'api_key', 'smm-key');
        ProviderSettings::set('boost', 'base_url', 'https://resellersmm.com');

        $service = $this->makeService();

        try {
            app(OrderService::class)->buyBoost($this->user, $service, 100);
            $this->fail('buyBoost must throw when the provider fails.');
        } catch (\DomainException) {
            // expected — the order is rolled back so nothing is charged
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_admin_can_add_provider_from_panel(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@provider.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageIntegrations::class)
            ->call('newProvider', 'numbers')
            ->set('name', 'Primary numbers')
            ->set('driver', 'generic')
            ->set('base_url', 'https://api.example.com')
            ->set('api_key', 'secret123')
            ->call('save')
            ->assertHasNoErrors();

        $provider = Provider::query()->where('channel', 'numbers')->firstOrFail();

        $this->assertSame('Primary numbers', $provider->name);
        $this->assertSame('secret123', $provider->api_key);
        $this->assertSame('https://api.example.com', $provider->base_url);
        $this->assertTrue($provider->active);
        $this->assertNotNull($provider->masked_key);
    }

    public function test_admin_can_sync_provider_balance_and_log_usage(): void
    {
        Http::fake([
            'panel.example.com/*' => Http::response(['balance' => 125.5], 200),
        ]);

        $provider = Provider::create([
            'channel' => 'boost',
            'name' => 'SMM panel',
            'driver' => 'smmpanel',
            'base_url' => 'https://panel.example.com/api/v2',
            'api_key' => 'smm-key',
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-sync@provider.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageIntegrations::class)
            ->call('sync', $provider->id)
            ->assertHasNoErrors();

        $this->assertSame('125.5', $provider->fresh()->balance);
        $this->assertNotNull($provider->fresh()->last_synced_at);

        $this->assertDatabaseHas('provider_logs', [
            'provider_id' => $provider->id,
            'type' => 'balance',
            'status' => 'success',
        ]);
    }

    public function test_grizzly_driver_cancels_with_status_8_not_complete(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::sequence()
                ->push('ACCESS_NUMBER:999:79991112233', 200)
                ->push('', 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'driver', 'grizzly');
        ProviderSettings::set('numbers', 'api_key', 'griz-key');
        ProviderSettings::set('numbers', 'base_url', 'https://api.grizzlysms.com');

        $service = $this->makeNumberService();
        $service->update(['provider_service_id' => 'wa', 'provider_country_id' => '47']);

        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);
        app(NumberPurchaseService::class)->cancel($this->user, $order);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);

        Http::assertSent(function ($request) {
            return $request['action'] === 'setStatus'
                && $request['id'] === '999'
                && $request['status'] === '8';
        });
    }

    public function test_smmpanel_driver_parses_service_catalog_with_service_key(): void
    {
        Http::fake([
            'resellersmm.com/*' => Http::response([
                'services' => [
                    ['service' => 1001, 'name' => 'Instagram Followers', 'category' => 'Instagram', 'rate' => 0.15, 'min' => 50, 'max' => 10000],
                    ['service' => 1002, 'name' => 'YouTube Views', 'category' => 'YouTube', 'rate' => 0.05, 'min' => 100, 'max' => 50000],
                ],
            ], 200),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'smmpanel');
        ProviderSettings::set('boost', 'api_key', 'smm-key');
        ProviderSettings::set('boost', 'base_url', 'https://resellersmm.com');
        ProviderSettings::set('boost', 'order_endpoint', '/api/v2');

        $catalog = ProviderFactory::boost()->catalog();

        $this->assertCount(2, $catalog);
        $this->assertSame('1001', $catalog[0]['provider_service_id']);
        $this->assertSame('Instagram Followers', $catalog[0]['name']);
        $this->assertSame('1002', $catalog[1]['provider_service_id']);
    }

    public function test_smmpanel_driver_parses_service_catalog_with_id_key(): void
    {
        Http::fake([
            'resellersmm.com/*' => Http::response([
                ['id' => 2001, 'name' => 'Facebook Likes', 'category' => 'Facebook', 'rate' => 0.1],
            ], 200),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'smmpanel');
        ProviderSettings::set('boost', 'api_key', 'smm-key');
        ProviderSettings::set('boost', 'base_url', 'https://resellersmm.com');
        ProviderSettings::set('boost', 'order_endpoint', '/api/v2');

        $catalog = ProviderFactory::boost()->catalog();

        $this->assertCount(1, $catalog);
        $this->assertSame('2001', $catalog[0]['provider_service_id']);
    }

    public function test_smmpanel_driver_fetch_status_accepts_numeric_complete_status(): void
    {
        Http::fake([
            'resellersmm.com/*' => Http::response(['status' => 'Completed', 'charge' => 0.5], 200),
        ]);

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'smmpanel');
        ProviderSettings::set('boost', 'api_key', 'smm-key');
        ProviderSettings::set('boost', 'base_url', 'https://resellersmm.com');
        ProviderSettings::set('boost', 'order_endpoint', '/api/v2');

        $result = ProviderFactory::boost()->status('23501');

        $this->assertTrue($result['success']);
        $this->assertSame('Completed', $result['status']);
    }

    public function test_grizzly_driver_get_status_accepts_status_ok_with_code(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('STATUS_OK:99999', 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'driver', 'grizzly');
        ProviderSettings::set('numbers', 'api_key', 'griz-key');
        ProviderSettings::set('numbers', 'base_url', 'https://api.grizzlysms.com');

        $driver = ProviderFactory::number();

        $result = $driver->getStatus('123');

        $this->assertTrue($result['success']);
        $this->assertSame('received', $result['status']);
    }

    public function test_grizzly_driver_get_status_maps_numeric_complete_to_received(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('6', 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'driver', 'grizzly');
        ProviderSettings::set('numbers', 'api_key', 'griz-key');
        ProviderSettings::set('numbers', 'base_url', 'https://api.grizzlysms.com');

        $result = ProviderFactory::number()->getStatus('123');

        $this->assertTrue($result['success']);
        $this->assertSame('received', $result['status']);
    }

    public function test_grizzly_driver_get_status_maps_numeric_cancel_to_cancelled(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('8', 200),
        ]);

        ProviderSettings::set('numbers', 'enabled', true);
        ProviderSettings::set('numbers', 'driver', 'grizzly');
        ProviderSettings::set('numbers', 'api_key', 'griz-key');
        ProviderSettings::set('numbers', 'base_url', 'https://api.grizzlysms.com');

        $result = ProviderFactory::number()->getStatus('123');

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['status']);
    }

    public function test_router_fails_over_to_secondary_provider(): void
    {
        Http::fake([
            'api.primary.com/*' => Http::response(['error' => 'No stock'], 422),
            'api.backup.com/*' => Http::response(['success' => true, 'reference' => 'PROV-BACKUP-1', 'number' => '+2348111111111'], 200),
        ]);

        Provider::create([
            'channel' => 'numbers',
            'name' => 'Primary',
            'driver' => 'generic',
            'base_url' => 'https://api.primary.com',
            'api_key' => 'primary-key',
            'priority' => 0,
        ]);
        $backup = Provider::create([
            'channel' => 'numbers',
            'name' => 'Backup',
            'driver' => 'generic',
            'base_url' => 'https://api.backup.com',
            'api_key' => 'backup-key',
            'priority' => 1,
        ]);

        $service = $this->makeNumberService();
        $order = app(NumberPurchaseService::class)->purchase($this->user, $service);

        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Order::SMS_WAITING, $order->sms_status);
        $this->assertSame('PROV-BACKUP-1', $order->provider_reference);
        $this->assertSame($backup->id, $order->provider_id);

        $this->assertDatabaseHas('provider_logs', [
            'provider_id' => $backup->id,
            'type' => 'order',
            'status' => 'success',
        ]);
    }
}
