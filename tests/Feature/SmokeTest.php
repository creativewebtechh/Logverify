<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Number;
use App\Models\NumberService;
use App\Models\Order;
use App\Models\Service;
use App\Models\Tool;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($user);
        app(WalletService::class)->credit($user, 50000, 'deposit', 'Test funding');

        return $user;
    }

    private function seedCatalogue(): void
    {
        Number::create(['country' => 'Nigeria', 'category' => 'sms', 'number' => '+2340000000000', 'masked_number' => '+234 (•••) •••-0000', 'price' => 1500, 'status' => 'available']);
        NumberService::create(['catalog_key' => 'manual:NG:whatsapp', 'country_code' => 'NG', 'country_name' => 'Nigeria', 'name' => 'WhatsApp', 'slug' => 'whatsapp', 'category' => 'sms', 'price' => 1500, 'cost' => 1200, 'eta' => '30 seconds', 'eta_seconds' => 30, 'stock' => 100, 'status' => NumberService::STATUS_ACTIVE, 'hidden' => false]);
        Tool::create(['name' => 'Test Tool', 'slug' => 'test-tool', 'description' => 'A test tool', 'price' => 2000, 'category' => 'automation', 'status' => 'active']);
        Service::create(['name' => 'Instagram Followers', 'slug' => 'ig-followers', 'description' => 'Boost', 'type' => 'social', 'platform' => 'instagram', 'price_per_unit' => 0.5, 'min_qty' => 50, 'max_qty' => 10000, 'status' => 'active']);
        Account::create(['platform' => 'instagram', 'title' => 'Aged Instagram account', 'description' => '2019, 5k followers', 'price' => 8000, 'status' => 'available']);
    }

    public function test_guest_pages_render(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();

        $this->get('/')->assertOk()->assertSee('Logverify');

        $this->get(route('about'))->assertOk()->assertSee('About');

        $this->get(route('login'))->assertDontSee('wa.me');
    }

    public function test_customer_pages_render(): void
    {
        $user = $this->makeCustomer();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk()->assertSee('Available balance');
        $this->get(route('wallet'))->assertOk()->assertSee('Fund your wallet')->assertSee('Monnify');
        $this->get(route('wallet.transactions'))->assertOk();
        $this->get(route('security'))->assertOk()->assertSee('Account security');
        $this->get(route('referrals'))->assertOk()->assertSee('Refer');
        $this->get(route('orders'))->assertOk();
        $this->get(route('notifications'))->assertOk();
    }

    public function test_wallet_withdrawal_is_not_available(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('wallet.withdraw'));

        $user = $this->makeCustomer();
        $this->actingAs($user);

        $this->get(route('wallet.transactions'))
            ->assertOk()
            ->assertDontSee('Withdrawals');
    }

    public function test_services_pages_render(): void
    {
        $this->seedCatalogue();
        $user = $this->makeCustomer();
        $this->actingAs($user);

        $this->get(route('numbers'))->assertOk()->assertSee('SMS verification numbers');
        $this->get(route('accounts'))->assertOk()->assertSee('Buy social accounts');
        $this->get(route('tools'))->assertOk()->assertSee('Buy tools');
        $this->get(route('boost'))->assertOk()->assertSee('Social media boost');
    }

    public function test_boost_purchase_and_orders(): void
    {
        $this->seedCatalogue();
        $user = $this->makeCustomer();
        $this->actingAs($user);

        $service = Service::first();
        Order::create([
            'user_id' => $user->id,
            'orderable_type' => $service::class,
            'orderable_id' => $service->id,
            'title' => $service->name,
            'quantity' => 100,
            'unit_price' => $service->price_per_unit,
            'total' => $service->priceFor(100),
            'status' => Order::STATUS_PAID,
            'reference' => 'ORD-TEST-1',
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        $this->get(route('orders'))->assertOk()->assertSee('Instagram Followers');
    }

    public function test_admin_pages_render(): void
    {
        $this->seedCatalogue();
        $this->makeCustomer();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.users'))->assertOk()->assertSee('Admin');
        $this->get(route('admin.numbers'))->assertOk();
        $this->get(route('admin.accounts'))->assertOk();
        $this->get(route('admin.tools'))->assertOk();
        $this->get(route('admin.services'))->assertOk();
        $this->get(route('admin.orders'))->assertOk();
        $this->get(route('admin.transactions'))->assertOk();
        $this->get(route('admin.integrations'))->assertOk()->assertSee('API Integrations');
        $this->get(route('admin.settings'))->assertOk()->assertSee('Settings');
        $this->get(route('admin.sms'))->assertOk();
    }
}
