<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        $user = User::create([
            'name' => 'Dash Customer',
            'email' => 'dash@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($user);
        app(WalletService::class)->credit($user, 25000, 'deposit', 'Test funding');

        return $user;
    }

    public function test_dashboard_renders_stats_recent_activity_and_services(): void
    {
        Service::create([
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

        $this->actingAs($this->makeCustomer());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Available balance')
            ->assertSee('Fund Wallet')
            ->assertSee('Quick actions')
            ->assertSee('Recent activity')
            ->assertSee('Test funding')
            ->assertSee('All services')
            ->assertSee('Instagram Followers')
            ->assertSee('Notifications')
            ->assertDontSee('Total earned');
    }

    public function test_dashboard_displays_announcement_when_configured(): void
    {
        Setting::set('announcement', 'Scheduled maintenance this weekend.');

        $this->actingAs($this->makeCustomer());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Announcement')
            ->assertSee('Scheduled maintenance this weekend.');
    }
}
