<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'name' => 'WhatsApp Bulk Sender',
                'description' => 'Send unlimited WhatsApp messages with personalized templates and scheduling.',
                'price' => 15000,
                'category' => 'automation',
                'icon' => 'chat',
                'stock' => 20,
                'featured' => true,
                'image' => 'demo/tool-whatsapp.png',
                'download_url' => 'https://example.com/download/whatsapp-bulk-sender',
                'badge' => 'Featured',
                'sort_order' => 1,
            ],
            [
                'name' => 'Instagram Auto Follower',
                'description' => 'Automated engagement tool that safely grows Instagram followers.',
                'price' => 12000,
                'category' => 'automation',
                'icon' => 'camera',
                'stock' => 15,
                'featured' => false,
                'badge' => 'Popular',
                'sort_order' => 2,
            ],
            [
                'name' => 'Twitter/X Growth Kit',
                'description' => 'Complete toolkit for organic Twitter growth, threads and scheduling.',
                'price' => 10000,
                'category' => 'automation',
                'icon' => 'hashtag',
                'stock' => 12,
                'featured' => false,
                'badge' => 'Best Value',
                'sort_order' => 3,
            ],
            [
                'name' => 'AI Content Generator',
                'description' => 'Generate viral captions, scripts and posts with advanced AI models.',
                'price' => 18000,
                'category' => 'ai',
                'icon' => 'sparkles',
                'stock' => 25,
                'featured' => true,
                'image' => 'demo/tool-ai.png',
                'badge' => 'Best Seller',
                'sort_order' => 4,
            ],
            [
                'name' => 'Auto-Reply Bot',
                'description' => '24/7 auto-responder bot for DMs and comments across major platforms.',
                'price' => 20000,
                'category' => 'ai',
                'icon' => 'robot',
                'stock' => 10,
                'featured' => true,
                'image' => 'demo/tool-bot.png',
                'badge' => 'Hot',
                'sort_order' => 5,
            ],
            [
                'name' => 'Hashtag Research Pro',
                'description' => 'Find high-performing hashtags and track their performance over time.',
                'price' => 8000,
                'category' => 'api',
                'icon' => 'search',
                'stock' => 30,
                'featured' => false,
                'image' => 'demo/tool-hashtag.png',
                'badge' => 'Best Value',
                'sort_order' => 6,
            ],
            [
                'name' => 'Analytics Dashboard',
                'description' => 'Unified analytics across all connected social accounts in real time.',
                'price' => 25000,
                'category' => 'api',
                'icon' => 'chart',
                'stock' => 8,
                'featured' => false,
                'image' => 'demo/tool-analytics.png',
                'badge' => 'New',
                'sort_order' => 7,
            ],
            [
                'name' => 'Post Scheduler',
                'description' => 'Plan and schedule posts across all platforms from one clean calendar.',
                'price' => 9000,
                'category' => 'automation',
                'icon' => 'calendar',
                'stock' => 18,
                'featured' => false,
                'image' => 'demo/tool-scheduler.png',
                'badge' => 'New',
                'sort_order' => 8,
            ],
        ];

        foreach ($tools as $tool) {
            $downloadUrl = $tool['download_url'] ?? null;
            $badge = $tool['badge'] ?? null;
            $sortOrder = $tool['sort_order'] ?? 100;
            unset($tool['download_url'], $tool['badge'], $tool['sort_order']);

            $data = [
                'name' => $tool['name'],
                'slug' => Str::slug($tool['name']),
                'description' => $tool['description'],
                'price' => $tool['price'],
                'category' => $tool['category'],
                'icon' => $tool['icon'],
                'status' => 'active',
                'stock' => $tool['stock'] ?? 1,
                'featured' => $tool['featured'] ?? false,
                'image' => $tool['image'] ?? null,
                'meta' => [
                    'badge' => $badge,
                    'sort_order' => $sortOrder,
                ],
            ];

            if ($downloadUrl !== null) {
                $data['download_url'] = $downloadUrl;
            }

            Tool::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
