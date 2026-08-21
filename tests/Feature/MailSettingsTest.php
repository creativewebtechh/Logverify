<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_when_not_configured(): void
    {
        $this->assertFalse(MailSettings::enabled());
        $this->assertNull(MailSettings::host());
        $this->assertSame(2525, MailSettings::port());
        $this->assertNull(MailSettings::username());
        $this->assertNull(MailSettings::password());
        $this->assertFalse(MailSettings::isConfigured());
    }

    public function test_admin_can_save_smtp_settings_from_settings_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@mail.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('smtp_enabled', true)
            ->set('smtp_host', 'smtp.example.com')
            ->set('smtp_port', 465)
            ->set('smtp_username', 'user@example.com')
            ->set('smtp_password', 'secret123')
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_from_address', 'no-reply@example.com')
            ->call('saveSmtp')
            ->assertHasNoErrors();

        $this->assertTrue(MailSettings::enabled());
        $this->assertSame('smtp.example.com', MailSettings::host());
        $this->assertSame(465, MailSettings::port());
        $this->assertSame('user@example.com', MailSettings::username());
        $this->assertSame('secret123', MailSettings::password());
        $this->assertSame('ssl', MailSettings::encryption());
        $this->assertTrue(MailSettings::isConfigured());

        $row = Setting::where('key', 'smtp.password')->first();
        $this->assertNotNull($row);
        $this->assertNotSame('secret123', $row->value);
    }

    public function test_host_is_required_when_smtp_enabled(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@mail.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('smtp_enabled', true)
            ->set('smtp_host', '')
            ->call('saveSmtp')
            ->assertHasErrors('smtp_host');
    }

    public function test_apply_sets_runtime_mail_configuration(): void
    {
        Setting::set('smtp.enabled', true);
        Setting::set('smtp.host', 'mail.example.com');
        Setting::set('smtp.port', 2525);
        Setting::set('smtp.username', 'user');
        MailSettings::savePassword('pw123');
        Setting::set('smtp.from_address', 'a@example.com');

        MailSettings::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('mail.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('pw123', config('mail.mailers.smtp.password'));
        $this->assertSame('a@example.com', config('mail.from.address'));
    }

    public function test_apply_is_noop_when_disabled(): void
    {
        Setting::set('smtp.enabled', false);
        Setting::set('smtp.host', 'mail.example.com');

        MailSettings::apply();

        $this->assertSame('array', config('mail.default'));
        $this->assertNotSame('mail.example.com', config('mail.mailers.smtp.host'));
    }

    public function test_send_test_email_succeeds_with_active_mailer(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin3@mail.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('smtp_test_email', 'recipient@example.com')
            ->call('sendTestEmail')
            ->assertHasNoErrors()
            ->assertSet('smtp_test_email', '');
    }
}
