<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'platform' => 'instagram',
                'title' => 'Aged Instagram Account — 10K Followers',
                'description' => '2019 account with 10,000+ organic followers, 4% engagement and clean history. Great for brand use or reselling.',
                'price' => 45000,
                'stock' => 5,
                'featured' => true,
                'image' => 'demo/account-instagram.png',
                'credentials' => ['email' => 'sample.instagram@logverify.test', 'password' => 'demo-password', 'phone' => '+2348012345678'],
                'badge' => 'Featured',
                'sort_order' => 1,
            ],
            [
                'platform' => 'tiktok',
                'title' => 'TikTok Creator Account — 50K Followers',
                'description' => 'Verified creator account with 50,000 followers and strong video performance across the US and UK.',
                'price' => 80000,
                'stock' => 3,
                'featured' => true,
                'image' => 'demo/account-tiktok.png',
                'credentials' => ['email' => 'sample.tiktok@logverify.test', 'password' => 'demo-password'],
                'badge' => 'Hot',
                'sort_order' => 2,
            ],
            [
                'platform' => 'whatsapp',
                'title' => 'WhatsApp Business Number',
                'description' => 'Active WhatsApp Business account with API access enabled and clean sender reputation.',
                'price' => 12000,
                'stock' => 8,
                'featured' => false,
                'image' => 'demo/account-whatsapp.png',
                'credentials' => ['phone' => '+447700900123'],
                'badge' => 'Popular',
                'sort_order' => 3,
            ],
            [
                'platform' => 'telegram',
                'title' => 'Telegram Premium Account',
                'description' => 'Telegram Premium subscription account with a premium username. Ready to use for channels and bots.',
                'price' => 8000,
                'stock' => 6,
                'featured' => false,
                'image' => 'demo/account-telegram.png',
                'credentials' => ['phone' => '+447700900456', 'password' => 'demo-password'],
                'badge' => 'Best Value',
                'sort_order' => 4,
            ],
            [
                'platform' => 'facebook',
                'title' => 'Facebook Marketplace Account',
                'description' => 'Warm Facebook account with Marketplace access and payment method already verified.',
                'price' => 15000,
                'stock' => 4,
                'featured' => false,
                'image' => 'demo/account-facebook.png',
                'credentials' => ['email' => 'sample.facebook@logverify.test', 'password' => 'demo-password'],
                'badge' => 'New',
                'sort_order' => 5,
            ],
            [
                'platform' => 'twitter',
                'title' => 'Twitter/X Verification-Ready Account',
                'description' => 'Aged Twitter account with 5,000 followers, established handle and clean activity history.',
                'price' => 22000,
                'stock' => 3,
                'featured' => true,
                'image' => 'demo/account-twitter.png',
                'credentials' => ['email' => 'sample.twitter@logverify.test', 'password' => 'demo-password'],
                'badge' => 'Featured',
                'sort_order' => 6,
            ],
            [
                'platform' => 'linkedin',
                'title' => 'LinkedIn Recruiter Account',
                'description' => 'Recruiter seat with 2,000+ professional connections, clean history and open inbox. Ideal for hiring and outreach.',
                'price' => 35000,
                'stock' => 2,
                'featured' => false,
                'image' => null,
                'credentials' => ['email' => 'sample.linkedin@logverify.test', 'password' => 'demo-password'],
                'badge' => 'New',
                'sort_order' => 7,
            ],
            [
                'platform' => 'snapchat',
                'title' => 'Snapchat Creator Account',
                'description' => 'Creator account with strong story engagement and public profile, ready for brand collaborations.',
                'price' => 18000,
                'stock' => 4,
                'featured' => false,
                'image' => null,
                'credentials' => ['email' => 'sample.snapchat@logverify.test', 'password' => 'demo-password'],
                'badge' => 'Popular',
                'sort_order' => 8,
            ],
            [
                'platform' => 'google',
                'title' => 'Google Ads Manager Account',
                'description' => 'Verified Google Ads manager seat with billing ready and a clean campaign history.',
                'price' => 28000,
                'stock' => 3,
                'featured' => false,
                'image' => null,
                'credentials' => ['email' => 'sample.google@logverify.test', 'password' => 'demo-password'],
                'badge' => 'Best Value',
                'sort_order' => 9,
            ],
        ];

        foreach ($accounts as $account) {
            $credentials = $account['credentials'] ?? null;
            $badge = $account['badge'] ?? null;
            $sortOrder = $account['sort_order'] ?? 100;
            unset($account['credentials'], $account['badge'], $account['sort_order']);

            Account::updateOrCreate(
                ['title' => $account['title']],
                $account + [
                    'status' => 'available',
                    'currency' => 'NGN',
                    'credentials' => $credentials,
                    'meta' => [
                        'badge' => $badge,
                        'sort_order' => $sortOrder,
                    ],
                ]
            );
        }
    }
}
