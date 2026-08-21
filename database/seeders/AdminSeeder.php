<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@logverify.test'],
            [
                'name' => 'Logverify Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_verified' => true,
                'status' => true,
                'email_verified_at' => now(),
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'pending_balance' => 0, 'total_credited' => 0, 'total_debited' => 0]
        );
    }
}
