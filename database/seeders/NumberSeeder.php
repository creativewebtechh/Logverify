<?php

namespace Database\Seeders;

use App\Models\Number;
use Illuminate\Database\Seeder;

class NumberSeeder extends Seeder
{
    public function run(): void
    {
        $numbers = [
            // Nigeria
            ['country' => 'Nigeria', 'category' => 'sms', 'masked_number' => '+234 (•••) •••-4021', 'price' => 1500, 'provider' => 'verimap', 'provider_service_id' => 'sms', 'badge' => 'Best Seller', 'sort_order' => 1],
            ['country' => 'Nigeria', 'category' => 'sms', 'masked_number' => '+234 (•••) •••-8817', 'price' => 1500, 'provider' => 'verimap', 'provider_service_id' => 'sms'],
            ['country' => 'Nigeria', 'category' => 'whatsapp', 'masked_number' => '+234 (•••) •••-1193', 'price' => 2200, 'provider' => 'smsactive', 'provider_service_id' => 'wa', 'badge' => 'Popular', 'sort_order' => 2],
            ['country' => 'Nigeria', 'category' => 'voice', 'masked_number' => '+234 (•••) •••-6640', 'price' => 1800, 'provider' => '5sim', 'provider_service_id' => 'vo'],
            // United States
            ['country' => 'United States', 'category' => 'sms', 'masked_number' => '+1 (•••) •••-4821', 'price' => 3500, 'provider' => 'verimap', 'provider_service_id' => 'sms', 'badge' => 'Top Rated', 'sort_order' => 3],
            ['country' => 'United States', 'category' => 'sms', 'masked_number' => '+1 (•••) •••-9926', 'price' => 3500, 'provider' => '5sim', 'provider_service_id' => 'sms'],
            ['country' => 'United States', 'category' => 'whatsapp', 'masked_number' => '+1 (•••) •••-2744', 'price' => 4200, 'provider' => 'smsactive', 'provider_service_id' => 'wa', 'badge' => 'Hot', 'sort_order' => 4],
            ['country' => 'United States', 'category' => 'voice', 'masked_number' => '+1 (•••) •••-7580', 'price' => 3900, 'provider' => '5sim', 'provider_service_id' => 'vo'],
            // United Kingdom
            ['country' => 'United Kingdom', 'category' => 'sms', 'masked_number' => '+44 (•••) •••-7319', 'price' => 3100, 'provider' => 'verimap', 'provider_service_id' => 'sms', 'badge' => 'Popular', 'sort_order' => 5],
            ['country' => 'United Kingdom', 'category' => 'whatsapp', 'masked_number' => '+44 (•••) •••-0284', 'price' => 3800, 'provider' => 'smsactive', 'provider_service_id' => 'wa'],
            ['country' => 'United Kingdom', 'category' => 'voice', 'masked_number' => '+44 (•••) •••-5462', 'price' => 3500, 'provider' => '5sim', 'provider_service_id' => 'vo'],
            // India
            ['country' => 'India', 'category' => 'sms', 'masked_number' => '+91 (•••) •••-8891', 'price' => 900, 'provider' => 'verimap', 'provider_service_id' => 'sms', 'badge' => 'Best Value', 'sort_order' => 6],
            ['country' => 'India', 'category' => 'sms', 'masked_number' => '+91 (•••) •••-3360', 'price' => 900, 'provider' => '5sim', 'provider_service_id' => 'sms'],
            ['country' => 'India', 'category' => 'whatsapp', 'masked_number' => '+91 (•••) •••-6725', 'price' => 1200, 'provider' => 'smsactive', 'provider_service_id' => 'wa'],
            // Canada
            ['country' => 'Canada', 'category' => 'sms', 'masked_number' => '+1 (•••) •••-3398', 'price' => 3300, 'provider' => 'verimap', 'provider_service_id' => 'sms', 'badge' => 'New', 'sort_order' => 7],
            ['country' => 'Canada', 'category' => 'voice', 'masked_number' => '+1 (•••) •••-5073', 'price' => 3600, 'provider' => '5sim', 'provider_service_id' => 'vo'],
            // Germany
            ['country' => 'Germany', 'category' => 'sms', 'masked_number' => '+49 (•••) •••-1187', 'price' => 2900, 'provider' => 'verimap', 'provider_service_id' => 'sms', 'badge' => 'New', 'sort_order' => 8],
            ['country' => 'Germany', 'category' => 'whatsapp', 'masked_number' => '+49 (•••) •••-6402', 'price' => 3400, 'provider' => 'smsactive', 'provider_service_id' => 'wa'],
            // UAE
            ['country' => 'United Arab Emirates', 'category' => 'sms', 'masked_number' => '+971 (•••) •••-2290', 'price' => 2700, 'provider' => 'verimap', 'provider_service_id' => 'sms'],
            ['country' => 'United Arab Emirates', 'category' => 'whatsapp', 'masked_number' => '+971 (•••) •••-7815', 'price' => 3200, 'provider' => 'smsactive', 'provider_service_id' => 'wa'],
            // South Africa
            ['country' => 'South Africa', 'category' => 'sms', 'masked_number' => '+27 (•••) •••-9054', 'price' => 1600, 'provider' => 'verimap', 'provider_service_id' => 'sms'],
            ['country' => 'South Africa', 'category' => 'whatsapp', 'masked_number' => '+27 (•••) •••-4338', 'price' => 2100, 'provider' => 'smsactive', 'provider_service_id' => 'wa'],
            // Kenya
            ['country' => 'Kenya', 'category' => 'sms', 'masked_number' => '+254 (•••) •••-1172', 'price' => 1400, 'provider' => 'verimap', 'provider_service_id' => 'sms'],
            ['country' => 'Kenya', 'category' => 'whatsapp', 'masked_number' => '+254 (•••) •••-8820', 'price' => 1900, 'provider' => 'smsactive', 'provider_service_id' => 'wa'],
        ];

        foreach ($numbers as $number) {
            $badge = $number['badge'] ?? null;
            $sortOrder = $number['sort_order'] ?? 100;
            unset($number['badge'], $number['sort_order']);

            Number::updateOrCreate(
                ['masked_number' => $number['masked_number']],
                [...$number, 'meta' => [
                    'badge' => $badge,
                    'sort_order' => $sortOrder,
                ]]
            );
        }
    }
}
