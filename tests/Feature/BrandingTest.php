<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\Setting;
use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_used_when_not_configured(): void
    {
        $this->assertSame('Logverify', BrandingService::siteName());
        $this->assertSame('#2563eb', BrandingService::brandPrimary());
        $this->assertSame('#f97316', BrandingService::accentPrimary());
        $this->assertNull(BrandingService::faviconUrl());
    }

    public function test_saved_site_name_is_used_in_admin_wordmark(): void
    {
        Setting::set('site_name', 'VeriCore');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@branding.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('VeriCore');
    }

    public function test_brand_colors_generate_css_scale_and_apply(): void
    {
        Setting::set('branding.primary', '#0f766e');
        Setting::set('branding.accent', '#c026d3');

        $css = BrandingService::css();

        $this->assertSame('#0f766e', BrandingService::brandPrimary());
        $this->assertSame('#c026d3', BrandingService::accentPrimary());
        $this->assertStringContainsString('--color-brand-600: #0f766e;', $css);
        $this->assertStringContainsString('--color-accent-600: #c026d3;', $css);
    }

    public function test_admin_can_save_branding_from_settings_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@branding.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('brand_primary', '#7c3aed')
            ->set('brand_accent', '#059669')
            ->call('saveBranding')
            ->assertHasNoErrors();

        $this->assertSame('#7c3aed', BrandingService::brandPrimary());
        $this->assertSame('#059669', BrandingService::accentPrimary());
        $this->assertStringContainsString('--color-brand-600: #7c3aed;', BrandingService::css());
    }

    public function test_invalid_brand_color_is_rejected(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin3@branding.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('brand_primary', 'not-a-color')
            ->call('saveBranding')
            ->assertHasErrors('brand_primary');

        $this->assertSame('#2563eb', BrandingService::brandPrimary());
    }

    public function test_favicon_setting_used_when_file_exists(): void
    {
        $directory = public_path('images/branding');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = 'images/branding/favicon-test.png';
        file_put_contents(public_path($path), 'x');

        try {
            Setting::set('branding.favicon', $path);
            $this->assertSame($path, BrandingService::faviconUrl());
        } finally {
            @unlink(public_path($path));
        }
    }
}
