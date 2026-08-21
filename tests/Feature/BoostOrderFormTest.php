<?php

namespace Tests\Feature;

use App\Livewire\Services\BoostOrderForm;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\Providers\ProviderCatalog;
use App\Services\Providers\ProviderSettings;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BoostOrderFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(ProviderCatalog::CACHE_KEY);

        $this->user = User::create([
            'name' => 'Customer',
            'email' => 'customer@boost.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'transaction_pin' => bcrypt('1234'),
        ]);

        app(WalletService::class)->getOrCreateWallet($this->user);
        app(WalletService::class)->credit($this->user, 50000, 'deposit', 'Test funding');
    }

    private function makeService(string $providerId = ''): Service
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
            'provider_service_id' => $providerId,
            'status' => 'active',
        ]);
    }

    public function test_form_lists_platforms_and_services_from_catalogue(): void
    {
        $this->makeService();

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->assertSee('Choose a platform')
            ->assertSee('Instagram')
            ->assertSee('Instagram Followers');
    }

    public function test_selecting_a_service_applies_minimum_quantity_and_shows_charge(): void
    {
        $service = $this->makeService();
        $service->update(['min_qty' => 500, 'price_per_unit' => 2]);

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->assertSet('quantity', 500)
            ->assertSee('2.00')
            ->assertSee('1,000.00');
    }

    public function test_link_validation_rejects_a_wrong_platform_host(): void
    {
        $service = $this->makeService();

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('target', 'https://facebook.com/my-page')
            ->assertSee("doesn't look like a Instagram link");
    }

    public function test_quantity_below_minimum_shows_range_error(): void
    {
        $service = $this->makeService();

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('quantity', 10)
            ->assertSee('Quantity must be between 50 and 10,000');
    }

    public function test_customer_can_place_boost_order_without_provider(): void
    {
        Http::fake();

        $service = $this->makeService();

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('quantity', 100)
            ->set('target', 'https://instagram.com/testhandle')
            ->call('placeOrder')
            ->assertSet('showPinModal', true)
            ->assertHasNoErrors()
            ->set('pin', '1234')
            ->call('confirmPurchase')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'orderable_type' => Service::class,
            'orderable_id' => $service->id,
            'quantity' => 100,
            'status' => Order::STATUS_PROCESSING,
            'total' => 50.0,
        ]);

        $this->assertSame(49950.0, (float) $this->user->wallet->fresh()->balance);
        $this->assertSame(1, Notification::where('user_id', $this->user->id)->count());
    }

    public function test_wrong_transaction_pin_blocks_the_order(): void
    {
        Http::fake();

        $service = $this->makeService();

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('quantity', 100)
            ->set('target', 'https://instagram.com/testhandle')
            ->call('placeOrder')
            ->assertSet('showPinModal', true)
            ->set('pin', '0000')
            ->call('confirmPurchase')
            ->assertHasErrors('pin')
            ->assertSet('showPinModal', true);

        $this->assertSame(0, Order::count());
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);
    }

    public function test_purchase_without_a_pin_is_blocked(): void
    {
        $this->user->forceFill(['transaction_pin' => null])->save();

        $service = $this->makeService();

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('quantity', 100)
            ->set('target', 'https://instagram.com/testhandle')
            ->call('placeOrder')
            ->assertSet('showPinModal', false)
            ->assertSee('Set a transaction PIN');

        $this->assertSame(0, Order::count());
    }

    public function test_insufficient_balance_blocks_the_order(): void
    {
        $service = $this->makeService();

        app(WalletService::class)->debit($this->user, 49990, 'purchase', 'Spend it all');

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', (string) $service->id)
            ->set('quantity', 100)
            ->set('target', 'https://instagram.com/testhandle')
            ->call('placeOrder')
            ->assertSet('insufficientBalance', true)
            ->assertSee('Fund your wallet');

        $this->assertSame(0, Order::count());
    }

    public function test_provider_failure_never_charges_the_wallet(): void
    {
        Http::fake(function ($request) {
            $action = $request['action'] ?? null;

            if ($action === 'services') {
                return Http::response([
                    'success' => true,
                    'services' => [[
                        'id' => '56',
                        'name' => 'Instagram Followers',
                        'category' => 'Instagram Likes',
                        'rate' => 0.4,
                        'min' => 100,
                        'max' => 100000000,
                        'avg_time' => '5-40 minutes',
                        'link' => 'account link or username',
                        'desc' => 'Fast follower boost',
                        'refill' => true,
                        'dripfeed' => false,
                    ]],
                ], 200);
            }

            return Http::response(['error' => 'Out of stock'], 422);
        });

        ProviderSettings::set('boost', 'enabled', true);
        ProviderSettings::set('boost', 'driver', 'smmpanel');
        ProviderSettings::set('boost', 'api_key', 'smm-key');
        ProviderSettings::set('boost', 'base_url', 'https://api.smmpanel.com');
        ProviderSettings::set('boost', 'order_endpoint', '/api/v2');

        $service = $this->makeService('56');

        Livewire::actingAs($this->user)
            ->test(BoostOrderForm::class)
            ->set('platform', 'instagram')
            ->set('provider_service_id', '56')
            ->set('quantity', 100)
            ->set('target', 'https://instagram.com/testhandle')
            ->call('placeOrder')
            ->set('pin', '1234')
            ->call('confirmPurchase')
            ->assertSee('was not charged');

        $this->assertSame(0, Order::count());
        $this->assertSame(50000.0, (float) $this->user->wallet->fresh()->balance);

        Http::assertSent(function ($request) {
            return ($request['action'] ?? null) === 'add'
                && $request['service'] === '56'
                && $request['link'] === 'https://instagram.com/testhandle'
                && $request['quantity'] === 100;
        });
    }
}
