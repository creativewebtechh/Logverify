<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_by_default(): void
    {
        $this->assertFalse(WhatsAppService::enabled());
        $this->assertFalse(WhatsAppService::isActive());
        $this->assertNull(WhatsAppService::link());
        $this->assertSame('Hello LogVerify Support, I need assistance.', WhatsAppService::message());
    }

    public function test_admin_can_save_whatsapp_settings(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@wa.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('whatsapp_enabled', true)
            ->set('whatsapp_number', '2348167263577')
            ->set('whatsapp_message', 'Hello Support, I need assistance.')
            ->set('whatsapp_label', 'Chat on WhatsApp')
            ->call('saveWhatsApp')
            ->assertHasNoErrors();

        $this->assertTrue(WhatsAppService::enabled());
        $this->assertTrue(WhatsAppService::isActive());
        $this->assertSame('2348167263577', WhatsAppService::number());
        $this->assertSame('Chat on WhatsApp', WhatsAppService::label());
        $this->assertSame(
            'https://wa.me/2348167263577?text=Hello%20Support%2C%20I%20need%20assistance.',
            WhatsAppService::link()
        );
    }

    public function test_number_is_required_when_widget_enabled(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@wa.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('whatsapp_enabled', true)
            ->set('whatsapp_number', '')
            ->call('saveWhatsApp')
            ->assertHasErrors('whatsapp_number');
    }

    public function test_widget_appears_only_when_enabled(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('wa.me');

        Setting::set('whatsapp.enabled', true);
        Setting::set('whatsapp.number', '2348167263577');

        $this->get(route('login'))->assertOk()->assertSee('wa.me/2348167263577');
    }
}
