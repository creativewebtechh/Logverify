<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PaymentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_fall_back_to_config_when_not_saved(): void
    {
        config([
            'paystack.test_mode' => true,
            'paystack.secret_key' => 'sk_config',
            'paystack.public_key' => 'pk_config',
        ]);

        $this->assertSame('paystack', PaymentSettings::defaultGateway());
        $this->assertTrue(PaymentSettings::paystackTestMode());
        $this->assertSame('sk_config', PaymentSettings::paystackSecretKey());
        $this->assertSame('pk_config', PaymentSettings::paystackPublicKey());
    }

    public function test_admin_can_persist_gateway_settings_from_settings_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@payments.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('payment_default_gateway', 'monnify')
            ->set('paystack_public_key', 'pk_live_123')
            ->set('paystack_secret_key', 'sk_live_456')
            ->set('paystack_test_mode', false)
            ->set('monnify_client_key', 'MK_KEY')
            ->set('monnify_client_secret', 'MK_SECRET')
            ->set('monnify_contract_code', 'CC_123456')
            ->set('monnify_base_url', 'https://api.monnify.com')
            ->set('monnify_test_mode', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('monnify', PaymentSettings::defaultGateway());
        $this->assertSame('sk_live_456', PaymentSettings::paystackSecretKey());
        $this->assertFalse(PaymentSettings::paystackTestMode());
        $this->assertSame('MK_SECRET', PaymentSettings::monnifyClientSecret());
        $this->assertSame('https://api.monnify.com', PaymentSettings::monnifyBaseUrl());
        $this->assertFalse(PaymentSettings::monnifyTestMode());

        $service = app(PaymentService::class);
        $this->assertTrue($service->isConfigured());
        $this->assertTrue($service->monnifyIsConfigured());
        $this->assertSame('pk_live_123', $service->publicKey());
    }

    public function test_settings_page_prefills_saved_gateway_values(): void
    {
        PaymentSettings::set('default_gateway', 'monnify');
        PaymentSettings::set('paystack_secret_key', 'sk_saved');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@payments.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->assertSet('payment_default_gateway', 'monnify')
            ->assertSet('paystack_secret_key', 'sk_saved');
    }
}
