<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Instagram Followers', 'type' => 'followers', 'platform' => 'instagram', 'price_per_unit' => 0.15, 'min_qty' => 100, 'max_qty' => 50000, 'icon' => 'camera', 'provider_service_id' => 'ig-followers', 'description' => 'Real and safe Instagram followers delivered over time to protect your account.'],
            ['name' => 'Instagram Likes', 'type' => 'likes', 'platform' => 'instagram', 'price_per_unit' => 0.05, 'min_qty' => 50, 'max_qty' => 20000, 'icon' => 'heart', 'provider_service_id' => 'ig-likes', 'description' => 'Instant Instagram likes for your photos and reels.'],
            ['name' => 'Instagram Views', 'type' => 'views', 'platform' => 'instagram', 'price_per_unit' => 0.02, 'min_qty' => 100, 'max_qty' => 100000, 'icon' => 'eye', 'provider_service_id' => 'ig-views', 'description' => 'Reel and video views to kick-start your content reach.'],
            ['name' => 'Instagram Comments', 'type' => 'comments', 'platform' => 'instagram', 'price_per_unit' => 0.35, 'min_qty' => 10, 'max_qty' => 5000, 'icon' => 'message', 'provider_service_id' => 'ig-comments', 'description' => 'Custom comment packages with your choice of text or emoji sets.'],
            ['name' => 'TikTok Followers', 'type' => 'followers', 'platform' => 'tiktok', 'price_per_unit' => 0.20, 'min_qty' => 100, 'max_qty' => 50000, 'icon' => 'music', 'provider_service_id' => 'tt-followers', 'description' => 'High-quality TikTok followers delivered steadily for safety.'],
            ['name' => 'TikTok Likes', 'type' => 'likes', 'platform' => 'tiktok', 'price_per_unit' => 0.06, 'min_qty' => 50, 'max_qty' => 50000, 'icon' => 'heart', 'provider_service_id' => 'tt-likes', 'description' => 'Fast TikTok video likes for trending content.'],
            ['name' => 'TikTok Views', 'type' => 'views', 'platform' => 'tiktok', 'price_per_unit' => 0.03, 'min_qty' => 100, 'max_qty' => 200000, 'icon' => 'eye', 'provider_service_id' => 'tt-views', 'description' => 'Massive TikTok view packages to boost your video stats.'],
            ['name' => 'Facebook Followers', 'type' => 'followers', 'platform' => 'facebook', 'price_per_unit' => 0.18, 'min_qty' => 100, 'max_qty' => 50000, 'icon' => 'thumbs', 'provider_service_id' => 'fb-followers', 'description' => 'Facebook page followers to grow your audience quickly.'],
            ['name' => 'Facebook Likes', 'type' => 'likes', 'platform' => 'facebook', 'price_per_unit' => 0.08, 'min_qty' => 50, 'max_qty' => 20000, 'icon' => 'heart', 'provider_service_id' => 'fb-likes', 'description' => 'Page and post likes for stronger social proof.'],
            ['name' => 'YouTube Views', 'type' => 'views', 'platform' => 'youtube', 'price_per_unit' => 0.25, 'min_qty' => 1000, 'max_qty' => 1000000, 'icon' => 'play', 'provider_service_id' => 'yt-views', 'description' => 'Organic YouTube views delivered over a natural timespan.'],
            ['name' => 'YouTube Subscribers', 'type' => 'followers', 'platform' => 'youtube', 'price_per_unit' => 1.20, 'min_qty' => 50, 'max_qty' => 10000, 'icon' => 'tv', 'provider_service_id' => 'yt-subs', 'description' => 'Real YouTube subscribers to help you reach monetization.'],
            ['name' => 'Twitter/X Followers', 'type' => 'followers', 'platform' => 'twitter', 'price_per_unit' => 0.30, 'min_qty' => 100, 'max_qty' => 50000, 'icon' => 'hashtag', 'provider_service_id' => 'tw-followers', 'description' => 'Twitter/X followers to amplify your profile authority.'],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => Str::slug($service['name'])],
                [
                    'name' => $service['name'],
                    'slug' => Str::slug($service['name']),
                    'type' => $service['type'],
                    'platform' => $service['platform'],
                    'price_per_unit' => $service['price_per_unit'],
                    'min_qty' => $service['min_qty'],
                    'max_qty' => $service['max_qty'],
                    'icon' => $service['icon'],
                    'provider_service_id' => $service['provider_service_id'] ?? null,
                    'description' => $service['description'] ?? null,
                    'status' => 'active',
                ]
            );
        }
    }
}
