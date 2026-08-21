<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\Service;
use App\Services\Providers\ProviderCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(ProviderCatalog::CACHE_KEY);
    }

    public function test_entries_fall_back_to_local_services_without_a_provider(): void
    {
        Http::fake();

        Service::create([
            'name' => 'TikTok Followers',
            'slug' => 'tt-followers',
            'description' => 'Grow followers',
            'type' => 'social',
            'platform' => 'tiktok',
            'price_per_unit' => 1.5,
            'min_qty' => 100,
            'max_qty' => 100000,
            'avg_time' => '5-40 minutes',
            'status' => 'active',
        ]);

        $entries = app(ProviderCatalog::class)->entries();

        $this->assertCount(1, $entries);
        $this->assertSame((string) Service::first()->id, $entries[0]['provider_service_id']);
        $this->assertSame('tiktok', $entries[0]['platform']);
        $this->assertSame(1.5, $entries[0]['price_per_unit']);
        $this->assertSame(100, $entries[0]['min']);
        $this->assertSame('5-40 minutes', $entries[0]['avg_time']);
        Http::assertNothingSent();
    }

    public function test_entries_merge_provider_catalogue_with_local_pricing(): void
    {
        Http::fake([
            'panel.example.com/*' => Http::response([
                'success' => true,
                'services' => [
                    ['id' => '56', 'name' => 'New TikTok Followers', 'category' => 'TikTok Followers', 'rate' => 1.4, 'min' => 100, 'max' => 100000000, 'avg_time' => '5-40 minutes', 'link' => 'account link or username', 'desc' => 'Real followers, no drops', 'refill' => true, 'dripfeed' => false],
                    ['id' => '999', 'name' => 'Unlisted Service', 'category' => 'Other', 'rate' => 1.0, 'min' => 10, 'max' => 100],
                ],
            ], 200),
        ]);

        Provider::create([
            'channel' => Provider::CHANNEL_BOOST,
            'name' => 'SMM panel',
            'driver' => 'smmpanel',
            'base_url' => 'https://panel.example.com/api/v2',
            'api_key' => 'smm-key',
            'active' => true,
        ]);

        $service = Service::create([
            'name' => 'Our TikTok Followers',
            'slug' => 'tt-followers',
            'description' => 'Our description',
            'type' => 'social',
            'platform' => 'tiktok',
            'price_per_unit' => 2.5,
            'cost_per_unit' => 1.4,
            'min_qty' => 10,
            'max_qty' => 1000,
            'provider_service_id' => '56',
            'status' => 'active',
        ]);

        $entries = app(ProviderCatalog::class)->entries();

        $this->assertCount(1, $entries);
        $this->assertSame('56', $entries[0]['provider_service_id']);
        $this->assertSame($service->id, $entries[0]['local_id']);
        $this->assertSame(2.5, $entries[0]['price_per_unit']);
        $this->assertSame(1.4, $entries[0]['cost_per_unit']);
        $this->assertSame(100, $entries[0]['min']);
        $this->assertSame(100000000, $entries[0]['max']);
        $this->assertSame('5-40 minutes', $entries[0]['avg_time']);
        $this->assertSame('account link or username', $entries[0]['link']);
        $this->assertSame('Real followers, no drops', $entries[0]['description']);
        $this->assertTrue($entries[0]['refill']);
        $this->assertFalse($entries[0]['dripfeed']);
    }

    public function test_forget_rebuilds_the_cached_catalogue(): void
    {
        Http::fake([
            'panel.example.com/*' => Http::response(['success' => true, 'services' => []]),
        ]);

        Provider::create([
            'channel' => Provider::CHANNEL_BOOST,
            'name' => 'SMM panel',
            'driver' => 'smmpanel',
            'base_url' => 'https://panel.example.com/api/v2',
            'api_key' => 'smm-key',
            'active' => true,
        ]);

        Service::create([
            'name' => 'TikTok Followers',
            'slug' => 'tt-followers',
            'description' => 'Grow',
            'type' => 'social',
            'platform' => 'tiktok',
            'price_per_unit' => 1.0,
            'min_qty' => 10,
            'max_qty' => 1000,
            'status' => 'active',
        ]);

        $catalog = app(ProviderCatalog::class);

        $catalog->entries();
        $catalog->entries();

        Http::assertSentCount(1);

        $catalog->forget();
        $catalog->entries();

        Http::assertSentCount(2);
    }
}
