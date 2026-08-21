<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            NumberSeeder::class,
            AccountSeeder::class,
            ToolSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
